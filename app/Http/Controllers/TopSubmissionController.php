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
        $customers = Customer::all();
        return view('top_submissions.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);

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

        return redirect()->route('top-submissions.index')->with('success', 'Pengajuan berhasil dikirim.');
    }


    // --- FITUR UNTUK MANAGER (BISNIS / OPS) ---

    // 3. List Pengajuan Masuk
    public function index()
    {
        // Tampilkan semua pengajuan, urutkan dari yang terbaru
        $submissions = TopSubmission::with(['sales', 'customer'])->latest()->get();
        return view('top_submissions.index', compact('submissions'));
    }

    // UPDATE LOGIC APPROVE (PENTING!)
    public function approve($id)
    {
        $submission = TopSubmission::findOrFail($id);
        $salesUser = $submission->sales;
        $customer = $submission->customer;

        if ($submission->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses.');
        }

        // HITUNG SELISIH (DELTA)
        // Berapa tambahan limit yang diminta dibanding limit customer sekarang?
        $currentLimit = $customer->credit_limit;
        $requestedLimit = $submission->submission_limit;
        $neededQuota = $requestedLimit - $currentLimit;

        // Jika butuh tambahan kuota (neededQuota > 0), cek saldo sales
        if ($neededQuota > 0) {
            if ($salesUser->credit_limit_quota < $neededQuota) {
                return back()->with('error', "Gagal! Sisa kuota kredit Sales tidak cukup untuk penambahan Rp " . number_format($neededQuota));
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

        return back()->with('success', 'TOP Disetujui! Data customer diperbarui.');
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
