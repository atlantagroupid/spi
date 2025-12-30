<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\QuotaRequest; // Pastikan model ini dibuat
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
        $user = Auth::user();

        // A. Jika Sales/Bawahan: Tampilkan form history pengajuan dia
        if (in_array($user->role, ['sales', 'sales_store', 'sales_field'])) {
            $myRequests = QuotaRequest::where('user_id', $user->id)->latest()->get();
            return view('quotas.index_sales', compact('myRequests', 'user'));
        }

        // B. Jika Manager: Tampilkan daftar request dari bawahan
        // Manager Bisnis lihat request Sales. Manager Ops lihat request Manager Bisnis.

        $pendingRequests = QuotaRequest::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Filter: Manager Bisnis hanya melihat request dari Sales
        if ($user->role == 'manager_bisnis') {
            $pendingRequests = $pendingRequests->filter(function($req) {
                return in_array($req->user->role, ['sales', 'sales_store', 'sales_field']);
            });
        }

        // Manager Ops juga bisa lihat list semua user untuk setting manual (fitur lama Bapak)
        $allUsers = [];
        if ($user->role == 'manager_operasional') {
            $allUsers = User::whereIn('role', ['manager_bisnis', 'sales', 'sales_store', 'sales_field'])
                ->orderBy('role')->get();
        }

        return view('quotas.index_manager', compact('pendingRequests', 'allUsers', 'user'));
    }

    // =================================================================
    // 2. PROSES PENGAJUAN (Sales/Mgr Bisnis Minta Limit)
    // =================================================================
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

    // =================================================================
    // 3. PROSES PERSETUJUAN (Manager Approve/Reject)
    // =================================================================
    public function approve(Request $request, $id)
    {
        $manager = Auth::user();
        $quotaReq = QuotaRequest::with('user')->findOrFail($id);
        $amount = $quotaReq->amount;

        // Validasi Role
        if (!in_array($manager->role, ['manager_operasional', 'manager_bisnis'])) {
            abort(403);
        }

        if ($request->action == 'reject') {
            $quotaReq->update(['status' => 'rejected', 'approver_id' => $manager->id]);
            return back()->with('success', 'Pengajuan ditolak.');
        }

        // --- LOGIKA TRANSFER LIMIT ---
        DB::beginTransaction();
        try {
            // Cek 1: Jika Manager Bisnis, pastikan limit dia cukup untuk dipinjamkan
            if ($manager->role == 'manager_bisnis') {
                if ($manager->credit_limit_quota < $amount) {
                    return back()->with('error', 'Limit Anda tidak cukup! Sisa limit Anda: Rp ' . number_format($manager->credit_limit_quota) . '. Silakan ajukan ke Manager Ops.');
                }

                // Potong limit Manager Bisnis (Transfer)
                $manager->decrement('credit_limit_quota', $amount);
            }

            // Cek 2: Manager Ops (Sumber Dana Utama)
            // Manager Ops diasumsikan punya kuasa penuh, limitnya tidak perlu dipotong (atau bisa dianggap tak terbatas),
            // TAPI dia menambah limit ke bawahan.

            // Tambah Limit ke Peminta (Sales / Manager Bisnis)
            $quotaReq->user->increment('credit_limit_quota', $amount);

            // Update Status
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

    // =================================================================
    // 4. UPDATE MANUAL (Fitur Lama - Khusus Manager Ops)
    // =================================================================
    public function updateManual(Request $request, $id)
    {
        if (Auth::user()->role !== 'manager_operasional') abort(403);

        $request->validate(['credit_limit_quota' => 'required|numeric']);

        User::findOrFail($id)->update(['credit_limit_quota' => $request->credit_limit_quota]);

        return back()->with('success', 'Limit berhasil diupdate manual.');
    }
}
