<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Approval; // Penting: Import Model Approval
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\HasImageUpload;

class OrderController extends Controller
{
    use HasImageUpload;

    // 1. TAMPILKAN FORM ORDER
    public function create()
    {
        $user = Auth::user();
        $query = Customer::orderBy('name');

        if ($user->role === 'sales') {
            $query->where('user_id', $user->id);
        }

        $customers = $query->get();
        $products = Product::where('stock', '>', 0)->orderBy('name')->get();

        return view('orders.create', compact('customers', 'products'));
    }

    // 2. PROSES SIMPAN ORDER
    public function store(Request $request)
    {
        // 1. Validasi Input
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

            // 2. Hitung Jatuh Tempo
            $dueDate = now();
            if ($request->payment_type === 'kredit' || $request->payment_type === 'top') {
                $days = (int) $request->top_days;
                if ($days == 0 && $customer->top_days > 0) {
                    $days = $customer->top_days;
                }
                $dueDate = now()->addDays($days);
            }

            // 3. Create Order Header
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

            // 4. Simpan Item & Kurangi Stok
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

            // 5. Buat Tiket Approval
            $orderWithItems = $order->load('items.product');

            Approval::create([
                'model_type'    => Order::class,
                'model_id'      => $order->id,
                'action'        => 'approve_order',
                'original_data' => null,
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

    // 3. RIWAYAT ORDER (INDEX)
    public function index(Request $request)
    {
        $salesList = User::where('role', 'sales')->orderBy('name')->get();
        $query = Order::with(['user', 'customer'])->latest();

        if ($request->filled('store_name')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->store_name . '%');
            });
        }

        if (Auth::user()->role === 'sales') {
            $query->where('user_id', Auth::id());
        } elseif ($request->filled('sales_id')) {
            $query->where('user_id', $request->sales_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('orders.index', compact('orders', 'salesList'));
    }

    // 4. DETAIL ORDER
    public function show(Order $order)
    {
        $order->load(['customer', 'items.product', 'paymentLogs']);
        return view('orders.show', compact('order'));
    }

    // 5. MANAGER: APPROVE
    public function approve(Order $order)
    {
        if (!in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis'])) {
            abort(403);
        }

        if ($order->status !== 'pending_approval') {
            return back()->with('error', 'Status order tidak valid untuk diapprove.');
        }

        $order->update(['status' => 'approved']);

        Approval::where('model_type', Order::class)
                ->where('model_id', $order->id)
                ->where('status', 'pending')
                ->update(['status' => 'approved']);

        return back()->with('success', 'Order berhasil disetujui!');
    }

    // 6. MANAGER: REJECT
    public function reject(Order $order)
    {
        if (!in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis'])) {
            abort(403);
        }

        $order->update(['status' => 'rejected']);

        foreach ($order->items as $item) {
            $item->product->increment('stock', $item->quantity);
        }

        Approval::where('model_type', Order::class)
                ->where('model_id', $order->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

        return back()->with('success', 'Order ditolak dan stok dikembalikan.');
    }

    // =========================================================================
    // 7. [DIPERBAIKI] PROSES UPLOAD SURAT JALAN & REVISI
    // =========================================================================
    public function processOrder(Request $request, $id)
    {
        // Catatan: Parameter ganti jadi $id agar aman, lalu find manual
        $order = Order::findOrFail($id);

        if (Auth::user()->role !== 'kasir') {
            abort(403, 'Hanya kasir yang boleh proses pengiriman.');
        }

        // Validasi Input (Sesuai View Modal)
        $request->validate([
            'delivery_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver_name'        => 'nullable|string|max:100',
            'is_revision'        => 'required|boolean'
        ]);

        // Upload File (Pakai Trait user)
        $filePath = $this->uploadCompressed(
            $request->file('delivery_proof'),
            'delivery_notes' // Folder penyimpanan
        );

        // --- SKENARIO A: PENGAJUAN REVISI (MASUK APPROVAL) ---
        if ($request->is_revision == '1') {

            Approval::create([
                'model_type'   => Order::class,
                'model_id'     => $order->id,
                'requester_id' => Auth::id(),
                'action'       => 'update_delivery_note', // Action khusus revisi
                'status'       => 'pending',
                'new_data'     => [
                    'invoice_number'     => $order->invoice_number,
                    'delivery_proof' => $filePath,
                    'driver_name'        => $request->driver_name,
                    'reason'             => 'Revisi Surat Jalan'
                ],
                'original_data' => [
                    'delivery_proof' => $order->delivery_proof, // Pastikan kolom ini ada di DB orders
                    'driver_name'        => $order->driver_name
                ]
            ]);

            return back()->with('success', 'Permintaan Revisi Surat Jalan berhasil dikirim ke Manager.');
        }

        // --- SKENARIO B: UPLOAD BARU (LANGSUNG UPDATE) ---
        else {
            if ($order->status !== 'approved' && $order->status !== 'processed') {
                return back()->with('error', 'Order belum disetujui Manager.');
            }

            // Update Data Order
            // Pastikan kolom 'delivery_note_file' dan 'driver_name' sudah ada di tabel orders
            // Jika kolom di DB Anda bernama 'delivery_proof', ubah key array di bawah ini.
            $order->update([
                'delivery_proof' => $filePath,
                'driver_name'        => $request->driver_name,
                'status'             => 'shipped', // Ubah status jadi DIKIRIM
                // 'payment_status'  => ... (Opsional: biarkan logic pembayaran terpisah)
            ]);

            // Logic tambahan jika Cash langsung Lunas (Opsional, sesuai SOP)
            if ($order->payment_type == 'cash') {
                 // $order->update(['status' => 'completed', 'payment_status' => 'paid']);
            }

            return back()->with('success', 'Surat Jalan berhasil diupload. Status Order berubah menjadi DIKIRIM.');
        }
    }

    // 8. TAMPILKAN FORM EDIT
    public function edit($id)
    {
        $order = Order::with('items')->findOrFail($id);
        $user = Auth::user();

        if ($user->role == 'sales' && $order->user_id != $user->id) abort(403);

        if (!in_array($order->status, ['pending_approval', 'rejected'])) {
            return back()->with('error', 'Order yang sudah diproses tidak bisa diedit.');
        }

        $query = Customer::orderBy('name');
        if ($user->role === 'sales') {
            $query->where('user_id', $user->id);
        }
        $customers = $query->get();
        $products = Product::where('stock', '>', 0)->orderBy('name')->get();

        return view('orders.edit', compact('order', 'customers', 'products'));
    }

    // 9. UPDATE ORDER
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
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
            $order->items()->delete();

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

            $updateData = [
                'total_price' => $calculatedTotal,
                'notes'       => $request->notes,
                'status'      => 'pending_approval',
            ];

            // Handle payment proof update logic if needed...

            $order->update($updateData);

            $approval = Approval::where('model_type', Order::class)
                                ->where('model_id', $order->id)
                                ->where('status', 'pending')
                                ->first();

            $orderWithItems = $order->load('items.product');

            if ($approval) {
                $approval->update(['new_data' => $orderWithItems->toArray()]);
            } else {
                Approval::create([
                    'model_type'    => Order::class,
                    'model_id'      => $order->id,
                    'action'        => 'approve_order',
                    'new_data'      => $orderWithItems->toArray(),
                    'status'        => 'pending',
                    'requester_id'  => Auth::id(),
                ]);
            }

            DB::commit();
            return redirect()->route('orders.show', $order->id)->with('success', 'Order berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    // 10. KONFIRMASI BARANG SAMPAI
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
        return back()->with('success', 'Barang dikonfirmasi diterima.');
    }

    // 11. EXPORT PDF INVOICE
    public function exportPdf($id)
    {
        $order = Order::with(['customer', 'items.product', 'user'])->findOrFail($id);
        $pdf = Pdf::loadView('orders.pdf', compact('order'));
        return $pdf->download('Invoice-' . $order->invoice_number . '.pdf');
    }

    // 12. EXPORT PDF LIST
    public function exportListPdf(Request $request)
    {
        // (Logika Filter sama seperti Index...)
        $query = Order::with(['user', 'customer'])->latest();
        // ... (Include filter logic here if needed) ...
        $orders = $query->get();
        $pdf = Pdf::loadView('orders.pdf_list', compact('orders'));
        return $pdf->download('laporan-order-' . date('Y-m-d') . '.pdf');
    }
}
