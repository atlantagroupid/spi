<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Notifications\InvoiceDueReminder;
use App\Exports\ReceivableExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\HasImageUpload;

// --- IMPORT MODELS ---
use App\Models\Approval;
use App\Models\PaymentLog;
use App\Models\Order;

class ReceivableController extends Controller
{
    use HasImageUpload;

    // 1. LIST UNPAID INVOICES
    public function index()
    {
        $invoices = Order::with(['customer', 'user', 'paymentLogs'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereIn('status', ['approved', 'processed', 'completed'])
            ->orderBy('due_date', 'asc')
            ->paginate(10);

        return view('receivables.index', compact('invoices'));
    }

    // 2. SEND REMINDERS (H-3 Due Date)
    public function sendReminders()
    {
        $orders = Order::where('payment_status', 'unpaid')
            ->whereDate('due_date', Carbon::now()->addDays(3)->format('Y-m-d'))
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            if ($order->user) {
                $order->user->notify(new InvoiceDueReminder($order, 3));
                $count++;
            }
        }

        return back()->with('success', "Berhasil! $count notifikasi dikirim ke sales.");
    }

    // 3. EXPORT EXCEL
    public function export()
    {
        $fileName = 'laporan_piutang_' . date('d-m-Y') . '.xlsx';
        return Excel::download(new ReceivableExport, $fileName);
    }

    // 4. COMPLETED (PAID) INVOICES
    public function completed()
    {
        $invoices = Order::with(['customer', 'user'])
            ->where('payment_status', 'paid')
            ->latest()
            ->paginate(10);

        return view('receivables.completed', compact('invoices'));
    }

    // 5. PRINT PDF
    public function printPdf(Request $request)
    {
        $query = Order::with(['user', 'customer'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereIn('status', ['approved', 'processed', 'completed'])
            ->latest();

        if ($request->filled('sales_id')) {
            $query->where('user_id', $request->sales_id);
        }

        $receivables = $query->get();

        $pdf = Pdf::loadView('pdf.receivables', compact('receivables'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan-Piutang.pdf');
    }

    // 6. SHOW DETAIL
    public function show($id)
    {
        $order = Order::with(['customer', 'paymentLogs.user'])->findOrFail($id);
        // Hitung yang sudah diapprove
        $paidAmount = $order->paymentLogs()->whereIn('status', ['approved', 'verified'])->sum('amount');
        // Hitung yang sedang pending (agar tidak overpay)
        $pendingAmount = $order->paymentLogs()->where('status', 'pending')->sum('amount');

        $remaining = $order->total_price - $paidAmount;

        return view('receivables.show', compact('order', 'paidAmount', 'pendingAmount', 'remaining'));
    }

    // 7. STORE PAYMENT (FIX: FORCE APPROVAL UNTUK SEMUA ROLE)
    public function store(Request $request, $id)
    {
        // 1. Validasi Input
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_date'   => 'required|date',
            'payment_method' => 'required',
            'proof_file'     => 'nullable|image|max:5120' // Max 5MB
        ]);

        $order = Order::findOrFail($id);

        // 2. Cek Overpayment (Mencegah bayar lebih dari hutang)
        $paidOrPending = $order->paymentLogs()
            ->whereIn('status', ['approved', 'verified', 'pending'])
            ->sum('amount');

        $sisaHutang = $order->total_price - $paidOrPending;

        if ($request->amount > $sisaHutang) {
            return back()->with('error', 'Nominal melebihi sisa hutang (termasuk yang menunggu approval).');
        }

        // 3. Handle File Upload (WEBP LOGIC)
        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $this->uploadCompressed(
                $request->file('proof_file'),
                'payment_proofs'
            );
        }

        DB::beginTransaction();
        try {
            // --- PERUBAHAN UTAMA: HAPUS LOGIC AUTO APPROVE ---
            // Siapapun yang input (Sales/Kasir/Manager), status awal wajib 'pending_approval'
            // Ini memaksa Manager masuk menu Approval -> Klik Setujui -> Limit Kembali.

            // A. Create Payment Log
            $paymentLog = PaymentLog::create([
                'order_id'       => $order->id,
                'user_id'        => Auth::id(),
                'amount'         => $request->amount,
                'payment_date'   => $request->payment_date,
                'payment_method' => $request->payment_method,
                'proof_file'     => $proofPath,
                'status'         => 'pending', // Wajib Pending
                'notes'          => $request->notes
            ]);

            // B. Buat Tiket Approval
            Approval::create([
                'model_type'    => PaymentLog::class,
                'model_id'      => $paymentLog->id,
                'action'        => 'approve_payment',
                'new_data'      => $paymentLog->toArray(),
                'status'        => 'pending',
                'requester_id'  => Auth::id(),
            ]);

            DB::commit();

            // Pesan disesuaikan
            $msg = 'Pembayaran disimpan. ';
            if (in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional', 'superadmin'])) {
                $msg .= 'Silakan menuju menu "Persetujuan" untuk memverifikasi dan mengembalikan limit kredit.';
            } else {
                $msg .= 'Menunggu verifikasi Manager.';
            }

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // 8. APPROVE PAYMENT (JIKA VIA MENU RECEIVABLES)
    // Note: Disarankan menggunakan menu Approval utama, tapi ini dipatch agar aman.
    public function approve($log_id)
    {
        if (!in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional'])) {
            abort(403);
        }

        DB::transaction(function () use ($log_id) {
            $log = PaymentLog::findOrFail($log_id);

            // Cek agar tidak double approve
            if ($log->status == 'approved' || $log->status == 'verified') {
                return;
            }

            $log->update(['status' => 'approved']);

            $order = Order::findOrFail($log->order_id);
            $totalPaid = $order->paymentLogs()->where('status', 'approved')->sum('amount');

            if ($totalPaid >= $order->total_price) {
                $order->payment_status = 'paid';
                if ($order->status == 'delivered') {
                    $order->status = 'completed';
                }
            } else {
                $order->payment_status = 'partial';
            }
            $order->save();

            // --- TAMBAHAN: KEMBALIKAN LIMIT KREDIT (SAFETY NET) ---
            if (in_array($order->payment_type, ['top', 'kredit'])) {
                if ($order->customer) {
                    $order->customer->increment('credit_limit', $log->amount);
                }
            }
            // -----------------------------------------------------

            // Update Tiket Approval jika ada
            Approval::where('model_type', PaymentLog::class)
                ->where('model_id', $log_id)
                ->where('status', 'pending')
                ->update(['status' => 'approved', 'approver_id' => Auth::id()]);
        });

        return back()->with('success', 'Pembayaran disetujui & Limit Kredit dikembalikan.');
    }

    // 9. REJECT PAYMENT
    public function reject($log_id)
    {
        if (!in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional'])) {
            abort(403);
        }

        DB::transaction(function () use ($log_id) {
            $log = PaymentLog::findOrFail($log_id);
            $log->update(['status' => 'rejected']);

            Approval::where('model_type', PaymentLog::class)
                ->where('model_id', $log_id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'approver_id' => Auth::id()]);
        });

        return back()->with('error', 'Pembayaran ditolak.');
    }
}
