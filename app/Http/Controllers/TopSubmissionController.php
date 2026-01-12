<?php

namespace App\Http\Controllers;

use App\Models\TopSubmission;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TopSubmissionController extends Controller
{
    // --- FITUR UNTUK SALES ---

    // 1. Form Pengajuan (Halaman Sales)
    public function create()
    {
        $user = Auth::user();

        // --- FILTER CUSTOMER BERDASARKAN ROLE ---

        // Cek apakah User adalah Sales (Baik Field maupun Store)
        if (in_array($user->role, ['sales_field', 'sales_store'])) {
            // Jika Sales: Hanya ambil customer yang user_id-nya sama dengan sales ini
            $customers = \App\Models\Customer::where('user_id', $user->id)
                ->orderBy('name', 'asc')
                ->get();
        } else {
            // Jika Manager/Admin: Tampilkan SEMUA customer
            $customers = \App\Models\Customer::orderBy('name', 'asc')->get();
        }

        // Return ke view create (sesuaikan nama view bapak)
        return view('top_submissions.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $messages = [
            'customer_id.required' => 'Pilih customer yang mau diajukan.',
            'limit_only.min'       => 'Nominal limit tidak boleh minus.',
            'days_only.min'        => 'Jumlah hari tenor minimal 1 hari.',
        ];

        $request->validate(['customer_id' => 'required|exists:customers,id'], $messages);

        $customer = Customer::findOrFail($request->customer_id);
        // Ambil tipe dari hidden input ('limit', 'days', atau 'both')
        $type = $request->input('submission_type', 'limit');

        $finalLimit = 0;
        $finalDays = 0;
        $noteType = '';

        // LOGIKA PERCABANGAN TAB
        if ($type === 'limit') {
            // Tab A: Hanya Limit
            $request->validate(['limit_only' => 'required|numeric|min:0']);

            $finalLimit = $request->limit_only;
            $finalDays = $customer->top_days ?? 30; // Hari pakai data lama
            $noteType = 'Kenaikan Plafon';
        } elseif ($type === 'days') {
            // Tab B: Hanya Hari
            $request->validate(['days_only' => 'required|integer|min:1']);

            $finalLimit = $customer->credit_limit; // Limit pakai data lama
            $finalDays = $request->days_only;
            $noteType = 'Perpanjangan Tenor';
        } elseif ($type === 'both') {
            // Tab C: Keduanya (Baru)
            $request->validate([
                'limit_both' => 'required|numeric|min:0',
                'days_both' => 'required|integer|min:1'
            ]);

            $finalLimit = $request->limit_both;
            $finalDays = $request->days_both;
            $noteType = 'Update Plafon & Tenor';
        }

        // Simpan ke Database
        TopSubmission::create([
            'sales_id'         => Auth::id(),
            'customer_id'      => $customer->id,
            'submission_limit' => $finalLimit,
            'submission_days'  => $finalDays,
            'status'           => 'pending',
            'notes'            => "Jenis Pengajuan: " . $noteType,
        ]);

        return redirect()->route('top-submissions.create')->with('success', 'Pengajuan berhasil dikirim ke Manager.');
    }


    // --- FITUR UNTUK MANAGER (BISNIS / OPS) ---

    // 3. List Pengajuan Masuk
    public function index()
    {
        $user = Auth::user();

        // Hanya Manager Bisnis dan Manager Operasional yang bisa akses
        if (!in_array($user->role, ['manager_bisnis', 'manager_operasional'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Tampilkan semua pengajuan, urutkan dari yang terbaru
        $submissions = \App\Models\TopSubmission::with(['user', 'customer'])
        ->where('status', 'pending')
        ->latest()
        ->paginate(10);

    return view('top_submissions.index', compact('submissions'));
    }

    // UPDATE LOGIC APPROVE (PENTING!)
    public function approve($id)
    {
        $submission = \App\Models\TopSubmission::findOrFail($id);
        $salesUser = $submission->user;
        $customer = $submission->customer;

        if ($submission->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah pernah diproses sebelumnya.');
        }

        // HITUNG SELISIH
        $currentLimit = $customer->credit_limit;
        $requestedLimit = $submission->submission_limit;
        $neededQuota = $requestedLimit - $currentLimit;

        // Cek Saldo Manager/Sales (Logic Anda: Sales yang dipotong kuotanya)
        if ($neededQuota > 0) {
            // Gunakan format rupiah agar jelas
            $sisaQuota = number_format($salesUser->credit_limit_quota, 0, ',', '.');
            $butuhQuota = number_format($neededQuota, 0, ',', '.');

            if ($salesUser->credit_limit_quota < $neededQuota) {
                return back()->with('error', "Gagal! Sisa kuota kredit Sales ($salesUser->name) hanya Rp $sisaQuota. Tidak cukup untuk menambah Rp $butuhQuota.");
            }
        }

        DB::transaction(function () use ($submission, $salesUser, $customer, $neededQuota, $requestedLimit) {
            // 1. Potong Kuota Sales (Hanya selisihnya)
            if ($neededQuota > 0) {
                $salesUser->decrement('credit_limit_quota', $neededQuota);
            }

            // Opsional: Jika limit diturunkan, kuota sales dikembalikan? (Tergantung kebijakan kantor)
            // if ($neededQuota < 0) { $salesUser->increment('credit_limit_quota', abs($neededQuota)); }

            // 2. Update Customer
            $customer->update([
                'credit_limit' => $requestedLimit,
                'top_days'     => $submission->submission_days,
            ]);

            // 3. Update Status
            $submission->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
            ]);
        });

        return back()->with('success', 'Pengajuan DISETUJUI. Limit customer berhasil diperbarui. ✅');
    }


    // 5. Logika Tolak Pengajuan
    public function reject(Request $request, $id)
    {
        $submission = TopSubmission::findOrFail($id);

        $submission->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'notes'       => $request->notes // Alasan penolakan
        ]);

        return back()->with('success', 'Pengajuan TOP ditolak.');
    }
}
