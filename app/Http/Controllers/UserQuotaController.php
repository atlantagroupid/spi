<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\QuotaRequest;
use App\Models\Order; // <--- WAJIB TAMBAH INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserQuotaController extends Controller
{
    // =================================================================
    // 1. HALAMAN UTAMA (LIST PENGAJUAN & MONITORING LIMIT)
    // =================================================================
    public function index()
    {
        $user = Auth::user()->fresh();

        // A. Jika Sales/Bawahan: Tampilkan form history pengajuan dia
        if (in_array($user->role, ['sales', 'sales_store', 'sales_field'])) {

            // --- LOGIKA HITUNG SISA LIMIT (SAMA PERSIS DENGAN DASHBOARD) ---
            $limitQuota = $user->credit_limit_quota ?? 0;
            $usedCredit = 0;

            // Hitung pemakaian kredit dari order yang belum lunas
            if ($limitQuota > 0) {
                $unpaidOrders = Order::where('user_id', $user->id)
                    ->whereIn('payment_type', ['top', 'kredit']) // Hanya order Kredit/TOP
                    ->where('payment_status', '!=', 'paid')      // Yang belum lunas
                    ->whereNotIn('status', ['cancelled', 'rejected']) // Abaikan yang batal
                    ->get();

                foreach ($unpaidOrders as $o) {
                    // Kurangi dengan cicilan yang sudah masuk (approved)
                    $paidAmount = $o->paymentLogs->where('status', 'approved')->sum('amount');
                    $usedCredit += ($o->total_price - $paidAmount);
                }
            }

            // Hasil Akhir Sisa Limit Real
            $remainingLimit = $limitQuota - $usedCredit;
            // ---------------------------------------------------------------

            $myRequests = QuotaRequest::where('user_id', $user->id)->latest()->get();

            // Kirim variabel $remainingLimit ke view
            return view('quotas.index_sales', compact('myRequests', 'user', 'remainingLimit'));
        }

        // B. Jika Manager (Logic Tetap)
        $pendingRequests = QuotaRequest::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        if ($user->role == 'manager_bisnis') {
            $pendingRequests = $pendingRequests->filter(function ($req) {
                return in_array($req->user->role, ['sales_store', 'sales_field']);
            });
        }

        $allUsers = [];
        if ($user->role == 'manager_operasional') {
            $allUsers = User::whereIn('role', ['manager_bisnis', 'sales_store', 'sales_field'])
                ->orderBy('role')->get();
        }

        return view('quotas.index_manager', compact('pendingRequests', 'allUsers', 'user'));
    }

    // ... (Fungsi store, approve, updateManual biarkan tetap sama) ...
   public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|min:5',
        ], [
            'amount.required' => 'Isi jumlah limit yang diminta.',
            'reason.required' => 'Berikan alasan kenapa butuh tambahan limit.',
            'reason.min'      => 'Alasan terlalu singkat.',
        ]);

        QuotaRequest::create([
            'user_id' => Auth::id(),
            'amount'  => $request->amount,
            'reason'  => $request->reason,
            'status'  => 'pending'
        ]);

        return back()->with('success', 'Permintaan limit terkirim. Tunggu persetujuan atasan.');
    }

    public function approve(Request $request, $id)
    {
        /** @var User $manager */
        $manager = Auth::user()->fresh(); // Ambil data terbaru (termasuk sisa kuota saat ini)
        $quotaReq = QuotaRequest::with('user')->findOrFail($id);
        $amount = $quotaReq->amount;

        // 1. Validasi Role: Hanya Manager yang boleh approve
        if (!in_array($manager->role, ['manager_operasional', 'manager_bisnis'])) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        // 2. Fitur Reject (Tolak Pengajuan)
        if ($request->action == 'reject') {
            $quotaReq->update([
                'status' => 'rejected',
                'approver_id' => $manager->id
            ]);
            return back()->with('success', 'Pengajuan limit ditolak.');
        }

        // 3. LOGIKA TRANSFER LIMIT BERTINGKAT
        DB::beginTransaction();
        try {
            // A. Cek Saldo Pengirim (Approver)
            // Siapapun yang approve (Ops/Bisnis), harus punya modal dulu.
            if ($manager->credit_limit_quota < $amount) {

                $sisaSaldo = number_format($manager->credit_limit_quota, 0, ',', '.');

                // Pesan Error yang berbeda tergantung siapa yang approve
                if ($manager->role == 'manager_operasional') {
                    $pesanError = "Gagal! Budget Kuota Utama (Pusat) tidak mencukupi. Sisa: Rp $sisaSaldo. Harap hubungi IT/Database Admin untuk top-up modal perusahaan.";
                } else {
                    $pesanError = "Gagal! Saldo Limit Anda tidak cukup (Sisa: Rp $sisaSaldo). Silakan ajukan penambahan limit ke Manager Operasional.";
                }

                return back()->with('error', $pesanError);
            }

            // B. Eksekusi Transfer
            // 1. Kurangi Saldo Atasan (Pemberi)
            $manager->decrement('credit_limit_quota', $amount);

            // 2. Tambah Saldo Bawahan (Penerima)
            $quotaReq->user->increment('credit_limit_quota', $amount);

            // 3. Update Status Tiket
            $quotaReq->update([
                'status' => 'approved',
                'approver_id' => $manager->id
            ]);

            DB::commit();
            return back()->with('success', 'Limit berhasil disetujui! Saldo Anda berkurang sebesar Rp ' . number_format($amount, 0, ',', '.'));

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Quota Approve Error: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat memproses limit.');
        }
    }
    
    public function updateManual(Request $request, $id)
    {
        if (Auth::user()->role !== 'manager_operasional') abort(403);
        $request->validate(['credit_limit_quota' => 'required|numeric']);
        User::findOrFail($id)->update(['credit_limit_quota' => $request->credit_limit_quota]);
        return back()->with('success', 'Limit berhasil diupdate manual.');
    }
}
