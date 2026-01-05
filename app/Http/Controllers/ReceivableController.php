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
use Illuminate\Support\Facades\Log;
use App\Traits\HasImageUpload;

// --- IMPORT MODELS ---
use App\Models\Approval;
use App\Models\PaymentLog;
use App\Models\Order;

class ReceivableController extends Controller
{
    use HasImageUpload;


    /**
     * Helper: Validasi Keamanan File Ekstra Cepat
     * Mencegah file berbahaya (.php, .exe) lolos dari validasi Laravel
     */
    private function validateFileSafety(Request $request, $fieldName)
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);

            // 1. Cek Ekstensi Berbahaya (Blacklist)
            $blockedExtensions = ['php', 'php7', 'phtml', 'exe', 'sh', 'bat', 'bin'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $blockedExtensions)) {
                return 'File berbahaya terdeteksi (Blocked Extension).';
            }

            // 2. Cek Ekstensi Valid (Whitelist)
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($ext, $allowedExtensions)) {
                 return 'Format file tidak diizinkan. Hanya JPG, PNG, dan PDF.';
            }
        }
        return null;
    }

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

        // IDOR Protection: Sales hanya bisa lihat receivables order miliknya sendiri
        $user = Auth::user();
        if (in_array($user->role, ['sales_field', 'sales_store']) && $order->user_id != $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // Hitung yang sudah diapprove
        $paidAmount = $order->paymentLogs()->whereIn('status', ['approved', 'verified'])->sum('amount');
        // Hitung yang sedang pending (agar tidak overpay)
        $pendingAmount = $order->paymentLogs()->where('status', 'pending')->sum('amount');

        $remaining = $order->total_price - $paidAmount;

        return view('receivables.show', compact('order', 'paidAmount', 'pendingAmount', 'remaining'));
    }

    public function store(Request $request, $id)
    {
        // --- 1. SECURITY CHECK (PRIORITAS UTAMA) ---
        // Ini kuncinya agar SecurityAuditTest PASS
        if ($error = $this->validateFileSafety($request, 'proof_file')) {
            return back()->withErrors(['proof_file' => $error])->withInput();
        }

        // --- 2. Validasi Input Laravel ---
        $messages = [
            'amount.required'       => 'Nominal pembayaran wajib diisi.',
            'amount.min'            => 'Nominal tidak valid (minimal 1 rupiah).',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'proof_file.image'      => 'Bukti transfer harus berupa gambar (JPG/PNG).',
            'proof_file.max'        => 'Ukuran bukti foto maksimal 5MB.',
        ];

        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_date'   => 'required|date',
            'payment_method' => 'required',
            // Kita tetap pasang validasi standar sebagai backup
            'proof_file'     => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120'
        ], $messages);

        $order = Order::findOrFail($id);

        // --- 3. Validasi Sisa Hutang ---
        $paidOrPending = $order->paymentLogs()
            ->whereIn('status', ['approved', 'verified', 'pending'])
            ->sum('amount');

        $sisaHutang = $order->total_price - $paidOrPending;

        // Re-validate amount with dynamic max
        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $sisaHutang,
        ], [
            'amount.max' => 'Nominal pembayaran melebihi sisa hutang! Maksimal: Rp ' . number_format($sisaHutang, 0, ',', '.'),
        ]);

        // --- 4. Proses Simpan dengan Transaksi ---
        DB::beginTransaction();
        try {
            // Handle File Upload
            $proofPath = null;
            if ($request->hasFile('proof_file')) {
                // Gunakan store() standar agar aman dan kompatibel
                $proofPath = $request->file('proof_file')->store('payment-proofs', 'public');
            }

            // A. Create Payment Log
            $paymentLog = PaymentLog::create([
                'order_id'       => $order->id,
                'user_id'        => Auth::id(),
                'amount'         => $request->amount,
                'payment_date'   => $request->payment_date,
                'payment_method' => $request->payment_method,
                'proof_file'     => $proofPath,
                'status'         => 'pending', // Wajib Pending
                'notes'          => $request->notes ?? null
            ]);

            // B. Buat Tiket Approval
            Approval::create([
                'model_type'   => PaymentLog::class,
                'model_id'     => $paymentLog->id,
                'action'       => 'approve_payment',
                'new_data'     => $paymentLog->toArray(),
                'status'       => 'pending',
                'requester_id' => Auth::id(),
            ]);

            DB::commit();

            // Pesan Sukses
            $msg = "Pembayaran berhasil disimpan! ✅";
            if (in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional', 'superadmin'])) {
                $msg .= "<br><small>Karena Anda Manager, silakan langsung verifikasi di menu <b>Persetujuan</b> agar limit kembali.</small>";
            } else {
                $msg .= "<br><small>Data telah dikirim ke Manager untuk diverifikasi.</small>";
            }

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error Store Payment: " . $e->getMessage());

            // Tangkap error jika upload gagal karena file corrupt/malicious
            return back()->withErrors(['proof_file' => 'Gagal memproses pembayaran. File mungkin tidak valid.'])->withInput();
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
