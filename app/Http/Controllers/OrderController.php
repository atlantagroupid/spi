<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\HasImageUpload;

class OrderController extends Controller
{
    use HasImageUpload;

    // =========================================================================
    // 1. FORM CREATE ORDER (SALES)
    // =========================================================================
    public function create()
    {
        $user = Auth::user();
        $query = Customer::orderBy('name');

        // Filter Customer milik Sales sendiri
        if (in_array($user->role, ['sales_field', 'sales_store'])) {
            $query->where('user_id', $user->id);
        }

        $customers = $query->get();
        $products = Product::where('stock', '>', 0)->orderBy('name')->get();

        return view('orders.create', compact('customers', 'products'));
    }

    // =========================================================================
    // 2. SIMPAN ORDER BARU
    // =========================================================================
    public function store(Request $request)
    {
        // A. Validasi
        $isKredit = $request->payment_type === 'kredit';
        $topRule = $isKredit ? 'required|integer|min:1' : 'nullable';

        $request->validate([
            'customer_id'   => 'required|exists:customers,id',
            'payment_type'  => 'required|in:cash,top,kredit',
            'top_days'      => $topRule,
            'product_id'    => 'required|array|min:1',
            'quantity'      => 'required|array|min:1',
            'quantity.*'    => 'integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::findOrFail($request->customer_id);

            // B. Hitung Jatuh Tempo
            $dueDate = now();
            if ($request->payment_type === 'kredit' || $request->payment_type === 'top') {
                $days = (int) $request->top_days;
                if ($days == 0 && $customer->top_days > 0) {
                    $days = $customer->top_days;
                }
                $dueDate = now()->addDays($days);
            }

            // C. Buat Header Order
            $order = Order::create([
                'user_id'        => Auth::id(),
                'customer_id'    => $customer->id,
                'invoice_number' => 'SO-' . date('Ymd') . '-' . rand(1000, 9999),
                'status'         => 'pending_approval',
                'payment_status' => 'unpaid',
                'total_price'    => 0, // Nanti diupdate setelah hitung item
                'due_date'       => $dueDate,
                'notes'          => $request->notes,
                'payment_type'   => $request->payment_type,
            ]);

            $calculatedTotal = 0;

            // D. Proses Item & Potong Stok
            if ($request->has('product_id')) {
                $countItems = count($request->product_id);
                for ($i = 0; $i < $countItems; $i++) {
                    $prodId = $request->product_id[$i];
                    $qty    = $request->quantity[$i];

                    // Lock for update untuk mencegah race condition stok
                    $product = Product::where('id', $prodId)->lockForUpdate()->first();

                    if ($product) {
                        if ($product->stock < $qty) {
                            throw new \Exception("Stok {$product->name} tidak cukup (Sisa: {$product->stock})");
                        }

                        $product->decrement('stock', $qty);

                        $finalPrice = ($product->discount_price > 0) ? $product->discount_price : $product->price;
                        $subtotal = $finalPrice * $qty;
                        $calculatedTotal += $subtotal;

                        OrderItem::create([
                            'order_id'   => $order->id,
                            'product_id' => $product->id,
                            'quantity'   => $qty,
                            'price'      => $finalPrice,
                        ]);
                    }
                }
            }

            $order->update(['total_price' => $calculatedTotal]);

            // E. Catat History & Tiket Approval
            $this->recordHistory($order, 'Dibuat', 'Order baru dibuat oleh Sales.');

            $orderWithItems = $order->load('items.product');
            Approval::create([
                'model_type'    => Order::class,
                'model_id'      => $order->id,
                'action'        => 'approve_order',
                'new_data'      => $orderWithItems->toArray(),
                'status'        => 'pending',
                'requester_id'  => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Order berhasil dibuat! Menunggu Approval Manager.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat order: ' . $e->getMessage())->withInput();
        }
    }

    // =========================================================================
    // 3. DAFTAR RIWAYAT ORDER (INDEX)
    // =========================================================================
    public function index(Request $request)
    {
        $salesList = User::whereIn('role', ['sales_field', 'sales_store'])->orderBy('name')->get();
        $query = Order::with(['user', 'customer'])->latest();

        // 1. FILTER KHUSUS SALES (Hanya lihat punya sendiri)
        if (in_array(Auth::user()->role, ['sales_field', 'sales_store'])) {
            $query->where('user_id', Auth::id());
        }
        // 2. FILTER KHUSUS NON-SALES (Manager, Kasir, Gudang)
        else {
            // PERBAIKAN: Sembunyikan order 'rejected' dari list mereka agar bersih
            $query->where('status', '!=', 'rejected');
        }

        // Filter Toko
        if ($request->filled('store_name')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->store_name . '%');
            });
        }

        // Filter Role Sales (Hanya lihat punya sendiri)
        if (in_array(Auth::user()->role, ['sales_field', 'sales_store'])) {
            $query->where('user_id', Auth::id());
        } elseif ($request->filled('sales_id')) {
            // Manager bisa filter berdasarkan sales ID
            $query->where('user_id', $request->sales_id);
        }

        // Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('orders.index', compact('orders', 'salesList'));
    }

    // =========================================================================
    // 4. DETAIL ORDER (SHOW)
    // =========================================================================
    public function show(Order $order)
    {
        // Load relasi 'latestApproval' agar kita dapat ID Ticket-nya
        $order->load(['customer', 'items.product', 'paymentLogs', 'histories.user', 'latestApproval']);

        // Ambil data approval (jika ada)
        $approval = $order->latestApproval;

        return view('orders.show', compact('order', 'approval'));
    }

    // =========================================================================
    // 5. KASIR: UPLOAD SURAT JALAN & PROSES KIRIM
    // =========================================================================
    public function processOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (Auth::user()->role !== 'kasir') abort(403);

        $request->validate([
            'delivery_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver_name'    => 'required|string|max:100', // Wajib diisi
            'is_revision'    => 'required|boolean'
        ]);

        // Upload File (Akan return path lengkap: delivery_notes/abc.webp)
        $filePath = $this->uploadCompressed(
            $request->file('delivery_proof'),
            'delivery_notes',
            $order->delivery_proof // Kirim file lama agar dihapus otomatis
        );
        // --- SKENARIO A: PENGAJUAN REVISI SURAT JALAN ---
        if ($request->is_revision == '1') {
            Approval::create([
                'model_type'   => Order::class,
                'model_id'     => $order->id,
                'requester_id' => Auth::id(),
                'action'       => 'update_delivery_note',
                'status'       => 'pending',
                'new_data'     => [
                    'delivery_proof' => $filePath,
                    'driver_name'    => $request->driver_name
                ],
                'original_data' => [
                    'delivery_proof' => $order->delivery_proof,
                    'driver_name'    => $order->driver_name
                ]
            ]);

            $this->recordHistory($order, 'Revisi SJ', 'Kasir mengajukan revisi Surat Jalan.');
            return back()->with('success', 'Permintaan Revisi Surat Jalan dikirim ke Manager.');
        }

        // SKENARIO B: Langsung Update (Untuk Kasir)
        else {
            // Kita izinkan update jika status Approved ATAU Shipped (Buat revisi typo)
            if (!in_array($order->status, ['approved', 'processed', 'shipped'])) {
                return back()->with('error', 'Status order tidak valid.');
            }

            $order->update([
                'delivery_proof' => $filePath,
                'driver_name'    => $request->driver_name,
                'status'         => 'shipped',
            ]);

            // [PENTING] Catat History
            $this->recordHistory($order, 'Dikirim', 'Surat Jalan diterbitkan. Driver: ' . $request->driver_name);

            return back()->with('success', 'Pengiriman diproses!');
        }
    }

    // =========================================================================
    // 6. SALES: EDIT FORM (HANYA JIKA PENDING/REJECTED)
    // =========================================================================
    public function edit($id)
    {
        $order = Order::with('items')->findOrFail($id);
        $user = Auth::user();

        // Cek Hak Akses Sales
        if (in_array($user->role, ['sales_field', 'sales_store']) && $order->user_id != $user->id) {
            abort(403);
        }

        // Cek Status (Hanya boleh edit jika Pending atau Ditolak)
        if (!in_array($order->status, ['pending_approval', 'rejected'])) {
            return back()->with('error', 'Order yang sudah diproses tidak bisa diedit.');
        }

        $query = Customer::orderBy('name');
        if (in_array($user->role, ['sales_field', 'sales_store'])) {
            $query->where('user_id', $user->id);
        }
        $customers = $query->get();
        $products = Product::where('stock', '>', 0)->orderBy('name')->get();

        return view('orders.edit', compact('order', 'customers', 'products'));
    }

    // =========================================================================
    // 7. SALES: UPDATE (AJUKAN ULANG REVISI)
    // =========================================================================
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (!in_array($order->status, ['pending_approval', 'rejected'])) {
            return back()->with('error', 'Gagal update. Status order sudah berubah.');
        }

        $request->validate([
            'product_id' => 'required|array',
            'quantity'   => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // 1. Kembalikan Stok Lama
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
            $order->items()->delete();

            // 2. Hitung Ulang & Potong Stok Baru
            $calculatedTotal = 0;
            $countItems = count($request->product_id);

            for ($i = 0; $i < $countItems; $i++) {
                $prodId = $request->product_id[$i];
                $qty    = $request->quantity[$i];

                $product = Product::where('id', $prodId)->lockForUpdate()->first();

                if ($product) {
                    if ($product->stock < $qty) {
                        throw new \Exception("Stok {$product->name} kurang (Sisa: {$product->stock})");
                    }
                    $product->decrement('stock', $qty);

                    $finalPrice = ($product->discount_price > 0) ? $product->discount_price : $product->price;
                    $subtotal = $finalPrice * $qty;
                    $calculatedTotal += $subtotal;

                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'quantity'   => $qty,
                        'price'      => $finalPrice,
                    ]);
                }
            }

            // 3. Update Header Order
            $order->update([
                'total_price'    => $calculatedTotal,
                'notes'          => $request->notes,
                'status'         => 'pending_approval', // Reset status jadi Pending
                'rejection_note' => null // Hapus catatan penolakan lama
            ]);

            // 4. Update Approval Ticket
            $orderWithItems = $order->load('items.product');
            $approval = Approval::where('model_type', Order::class)
                                ->where('model_id', $order->id)
                                ->where('status', 'rejected') // Cari yg direject sebelumnya
                                ->latest()
                                ->first();

            // Buat tiket baru jika yang lama sudah basi
            Approval::create([
                'model_type'   => Order::class,
                'model_id'     => $order->id,
                'action'       => 'approve_order',
                'new_data'     => $orderWithItems->toArray(),
                'status'       => 'pending',
                'requester_id' => Auth::id(),
            ]);

            // 5. Catat History
            $this->recordHistory($order, 'Revisi', 'Sales memperbaiki order & mengajukan ulang.');

            DB::commit();
            return redirect()->route('orders.show', $order->id)->with('success', 'Order diperbarui & diajukan ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 8. KONFIRMASI BARANG TIBA (DELIVERED/COMPLETED)
    // =========================================================================
    public function confirmArrival(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'shipped') {
            return back()->with('error', 'Order belum dikirim.');
        }

        $newStatus = 'delivered';
        if ($order->payment_status == 'paid') {
            $newStatus = 'completed';
        }

        $order->update(['status' => $newStatus]);

        $this->recordHistory($order, 'Sampai', 'Barang dikonfirmasi telah sampai di lokasi.');

        return back()->with('success', 'Barang dikonfirmasi diterima.');
    }

    // =========================================================================
    // 9. EXPORT PDF
    // =========================================================================
    public function exportPdf($id)
    {
        $order = Order::with(['customer', 'items.product', 'user'])->findOrFail($id);
        $pdf = Pdf::loadView('orders.pdf', compact('order'));
        return $pdf->download('Invoice-' . $order->invoice_number . '.pdf');
    }

    public function exportListPdf(Request $request)
    {
        $query = Order::with(['user', 'customer'])->latest();
        // (Tambahkan logika filter yang sama dengan index jika diperlukan)
        $orders = $query->get();
        $pdf = Pdf::loadView('orders.pdf_list', compact('orders'));
        return $pdf->download('laporan-order-' . date('Y-m-d') . '.pdf');
    }

    // =========================================================================
    // HELPER: RECORD HISTORY (JIKA MODEL BELUM ADA FUNGSI INI)
    // =========================================================================
    private function recordHistory($order, $action, $description = null)
    {
        // Cek apakah model Order punya fungsi recordHistory
        if (method_exists($order, 'recordHistory')) {
            $order->recordHistory($action, $description);
        } else {
            // Fallback manual jika Model belum diupdate
            \App\Models\OrderHistory::create([
                'order_id'    => $order->id,
                'user_id'     => Auth::id(),
                'action'      => $action,
                'description' => $description
            ]);
        }
    }

    // Method Khusus untuk Select2 AJAX
    public function searchProducts(Request $request)
    {
        $search = $request->search;   // Kata yang diketik sales
        $category = $request->category; // Filter kategori (opsional)

        $query = \App\Models\Product::query();

        // 1. Filter Berdasarkan Kategori (Jika dipilih)
        if ($category) {
            $query->where('category', $category);
        }

        // 2. Filter Berdasarkan Kata Kunci (Nama Produk)
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // 3. Ambil 20 data saja biar ringan
        $products = $query->limit(20)->get();

        // 4. Format data sesuai standar Select2
        $response = [];
        foreach ($products as $product) {
            $response[] = [
                'id' => $product->id,
                'text' => $product->name . " (Stok: $product->stock)", // Teks yang tampil
                // Data tambahan untuk Javascript (Harga & Stok)
                'price' => $product->price,
                'stock' => $product->stock
            ];
        }

        return response()->json($response);
    }
}
