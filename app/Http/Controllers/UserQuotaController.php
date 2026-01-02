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
            'reason' => 'required|string',
        ]);

        QuotaRequest::create([
            'user_id' => Auth::id(),
            'amount'  => $request->amount,
            'reason'  => $request->reason,
            'status'  => 'pending'
        ]);

        return back()->with('success', 'Pengajuan limit berhasil dikirim ke Atasan.');
    }

    public function approve(Request $request, $id)
    {
        /** @var User $manager */
        $manager = Auth::user()->fresh();
        $quotaReq = QuotaRequest::with('user')->findOrFail($id);
        $amount = $quotaReq->amount;

        if (!in_array($manager->role, ['manager_operasional', 'manager_bisnis'])) {
            abort(403);
        }

        if ($request->action == 'reject') {
            $quotaReq->update(['status' => 'rejected', 'approver_id' => $manager->id]);
            return back()->with('success', 'Pengajuan ditolak.');
        }

        DB::beginTransaction();
        try {
            if ($manager->role == 'manager_bisnis') {
                if ($manager->credit_limit_quota < $amount) {
                    return back()->with('error', 'Limit Anda tidak cukup! Sisa limit Anda: Rp ' . number_format($manager->credit_limit_quota) . '. Silakan ajukan ke Manager Ops.');
                }
                $manager->credit_limit_quota -= $amount;
                $manager->save();
            }

            $quotaReq->user->increment('credit_limit_quota', $amount);

            $quotaReq->update([
                'status' => 'approved',
                'approver_id' => $manager->id
            ]);

            DB::commit();
            return back()->with('success', 'Limit berhasil ditambahkan ke ' . $quotaReq->user->name);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
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
