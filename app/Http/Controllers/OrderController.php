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
    // HELPER: PUSAT FILTER (DIGUNAKAN OLEH INDEX & PDF)
    // =========================================================================
    private function getFilteredQuery(Request $request)
    {
        $query = Order::with(['user', 'customer'])->latest();

        // 1. FILTER BERDASARKAN ROLE (KEAMANAN DATA)
        if (in_array(Auth::user()->role, ['sales_field', 'sales_store'])) {
            // Sales hanya lihat punya sendiri
            $query->where('user_id', Auth::id());
        } else {
            // Manager/Gudang/Kasir lihat semua (kecuali Rejected, agar bersih)
            // Kecuali jika Manager memang sengaja filter status 'rejected', maka tampilkan
            if ($request->status != 'rejected') {
                $query->where('status', '!=', 'rejected');
            }
        }

        // 2. FILTER TOKO (Pencarian Nama Customer)
        if ($request->filled('store_name')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->store_name . '%');
            });
        }

        // 3. FILTER SALES (Khusus Manager)
        if ($request->filled('sales_id')) {
            $query->where('user_id', $request->sales_id);
        }

        // 4. FILTER TANGGAL (Rentang Waktu)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // 5. FILTER STATUS (Specific or All)
        if ($request->filled('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        return $query;
    }

    // =========================================================================
    // 1. INDEX (DAFTAR ORDER) - UPDATED
    // =========================================================================
    public function index(Request $request)
    {
        $salesList = User::whereIn('role', ['sales_field', 'sales_store'])->orderBy('name')->get();

        // Panggil Helper Filter di atas
        $query = $this->getFilteredQuery($request);

        // Paginate untuk tampilan web
        $orders = $query->paginate(10)->withQueryString();

        return view('orders.index', compact('orders', 'salesList'));
    }

    // =========================================================================
    // 2. EXPORT LIST PDF (LAPORAN) - UPDATED
    // =========================================================================
    public function exportListPdf(Request $request)
    {
        // Panggil Helper Filter yang SAMA PERSIS dengan Index
        $query = $this->getFilteredQuery($request);

        // Ambil SEMUA data (tanpa paginasi) untuk dicetak
        $orders = $query->get();

        // Judul Laporan Dinamis
        $title = 'Laporan Order';
        if ($request->start_date && $request->end_date) {
            $title .= ' (' . date('d/m/y', strtotime($request->start_date)) . ' - ' . date('d/m/y', strtotime($request->end_date)) . ')';
        }

        $pdf = Pdf::loadView('orders.pdf_list', compact('orders', 'title'))
                  ->setPaper('a4', 'landscape'); // Landscape agar muat banyak kolom

        return $pdf->download('laporan-order-' . date('Y-m-d-His') . '.pdf');
    }

    // =========================================================================
    // 3. FORM CREATE ORDER
    // =========================================================================
    public function create()
    {
        $user = Auth::user();
        $query = Customer::orderBy('name');

        if (in_array($user->role, ['sales_field', 'sales_store'])) {
            $query->where('user_id', $user->id);
        }

        $customers = $query->get();
        $products = Product::where('stock', '>', 0)->orderBy('name')->get();

        return view('orders.create', compact('customers', 'products'));
    }

    // =========================================================================
    // 4. SIMPAN ORDER BARU
    // =========================================================================
    public function store(Request $request)
    {
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

            $dueDate = now();
            if ($request->payment_type === 'kredit' || $request->payment_type === 'top') {
                $days = (int) $request->top_days;
                if ($days == 0 && $customer->top_days > 0) {
                    $days = $customer->top_days;
                }
                $dueDate = now()->addDays($days);
            }

            $order = Order::create([
                'user_id'        => Auth::id(),
                'customer_id'    => $customer->id,
                'invoice_number' => 'SO-' . date('Ymd') . '-' . rand(1000, 9999),
                'status'         => 'pending_approval',
                'payment_status' => 'unpaid',
                'total_price'    => 0,
                'due_date'       => $dueDate,
                'notes'          => $request->notes,
                'payment_type'   => $request->payment_type,
            ]);

            $calculatedTotal = 0;

            if ($request->has('product_id')) {
                $countItems = count($request->product_id);
                for ($i = 0; $i < $countItems; $i++) {
                    $prodId = $request->product_id[$i];
                    $qty    = $request->quantity[$i];

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
    // 5. DETAIL ORDER (SHOW)
    // =========================================================================
    public function show(Order $order)
    {
        $order->load(['customer', 'items.product', 'paymentLogs', 'histories.user', 'latestApproval']);
        $approval = $order->latestApproval;

        return view('orders.show', compact('order', 'approval'));
    }

    // =========================================================================
    // 6. PROSES SURAT JALAN (KASIR)
    // =========================================================================
    public function processOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (Auth::user()->role !== 'kasir') abort(403);

        $request->validate([
            'delivery_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver_name'    => 'required|string|max:100',
            'is_revision'    => 'required|boolean'
        ]);

        $filePath = $this->uploadCompressed(
            $request->file('delivery_proof'),
            'delivery_notes',
            $order->delivery_proof
        );

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
        } else {
            if (!in_array($order->status, ['approved', 'processed', 'shipped'])) {
                return back()->with('error', 'Status order tidak valid.');
            }

            $order->update([
                'delivery_proof' => $filePath,
                'driver_name'    => $request->driver_name,
                'status'         => 'shipped',
            ]);

            $this->recordHistory($order, 'Dikirim', 'Surat Jalan diterbitkan. Driver: ' . $request->driver_name);

            return back()->with('success', 'Pengiriman diproses!');
        }
    }

    // =========================================================================
    // 7. EDIT ORDER
    // =========================================================================
    public function edit($id)
    {
        $order = Order::with('items')->findOrFail($id);
        $user = Auth::user();

        if (in_array($user->role, ['sales_field', 'sales_store']) && $order->user_id != $user->id) {
            abort(403);
        }

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
    // 8. UPDATE ORDER (REVISI)
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
            // Kembalikan Stok Lama
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
            $order->items()->delete();

            // Hitung Ulang
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

            $order->update([
                'total_price'    => $calculatedTotal,
                'notes'          => $request->notes,
                'status'         => 'pending_approval',
                'rejection_note' => null
            ]);

            $orderWithItems = $order->load('items.product');
            Approval::create([
                'model_type'   => Order::class,
                'model_id'     => $order->id,
                'action'       => 'approve_order',
                'new_data'     => $orderWithItems->toArray(),
                'status'       => 'pending',
                'requester_id' => Auth::id(),
            ]);

            $this->recordHistory($order, 'Revisi', 'Sales memperbaiki order & mengajukan ulang.');

            DB::commit();
            return redirect()->route('orders.show', $order->id)->with('success', 'Order diperbarui & diajukan ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 9. KONFIRMASI TIBA
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
    // 10. EXPORT INVOICE SATUAN
    // =========================================================================
    public function exportPdf($id)
    {
        $order = Order::with(['customer', 'items.product', 'user'])->findOrFail($id);
        $pdf = Pdf::loadView('orders.pdf', compact('order'));
        return $pdf->download('Invoice-' . $order->invoice_number . '.pdf');
    }

    // =========================================================================
    // HELPER & AJAX
    // =========================================================================
    private function recordHistory($order, $action, $description = null)
    {
        if (method_exists($order, 'recordHistory')) {
            $order->recordHistory($action, $description);
        } else {
            \App\Models\OrderHistory::create([
                'order_id'    => $order->id,
                'user_id'     => Auth::id(),
                'action'      => $action,
                'description' => $description
            ]);
        }
    }

    public function searchProducts(Request $request)
    {
        $search = $request->search;
        $category = $request->category;

        $query = \App\Models\Product::query();

        if ($category) {
            $query->where('category', $category);
        }
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->limit(20)->get();

        $response = [];
        foreach ($products as $product) {
            $response[] = [
                'id' => $product->id,
                'text' => $product->name . " (Stok: $product->stock)",
                'price' => $product->price,
                'stock' => $product->stock
            ];
        }

        return response()->json($response);
    }
}
