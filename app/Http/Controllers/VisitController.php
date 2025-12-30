<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Customer;
use App\Models\User; // Tambahkan ini agar tidak error di bagian index
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VisitController extends Controller
{
    // ==========================================================
    // 1. FITUR INPUT MANUAL / CHECK-IN LANGSUNG
    // ==========================================================

    public function create()
    {
        $query = Customer::query();

        // JIKA SALES: Filter hanya toko miliknya
        if (Auth::user()->role === 'sales') {
            $query->where('user_id', Auth::id());
        }

        $customers = $query->orderBy('name')->get();

        return view('visits.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Cek Role: Apakah dia Sales Toko?
        // (Pastikan kolom 'role' sudah ada di tabel users, atau sesuaikan logikanya)
        $isStoreSales = $user->role === 'sales_store';

        // --- A. VALIDASI ---
        $rules = [
            'photo' => 'required|image|max:5120',
            'type'  => 'required|in:existing,new',
        ];

        // 1. Validasi GPS (Hanya Wajib untuk Sales Lapangan)
        if (!$isStoreSales) {
            $rules['latitude']  = 'required';
            $rules['longitude'] = 'required';
        }

        // 2. Validasi Tipe Customer (Logic Lama Tetap Jalan)
        if ($request->type == 'new') {
            $rules['new_name']    = 'required|string|max:255';
            $rules['new_phone']   = 'required|string|max:20';
            $rules['new_address'] = 'required|string';
        } else {
            $rules['customer_id'] = 'required|exists:customers,id';
        }

        // Eksekusi Validasi
        $request->validate($rules, [
            'latitude.required'    => 'Lokasi GPS wajib diambil untuk Sales Lapangan!',
            'customer_id.required' => 'Silakan pilih toko dari daftar.',
            'new_name.required'    => 'Nama toko baru wajib diisi.',
        ]);


        // --- B. LOGIKA PENYIMPANAN ---

        // 1. Tentukan Customer ID (Logic Lama)
        if ($request->type == 'new') {
            $newCustomer = Customer::create([
                'user_id'        => $user->id,
                'name'           => $request->new_name,
                'phone'          => $request->new_phone,
                'address'        => $request->new_address,
                'contact_person' => $request->new_contact ?? null,
                // Default Customer Baru: Cash Only (Limit 0)
                'top_days'       => null,
                'credit_limit'   => 0,
            ]);
            $customerId = $newCustomer->id;
            $msg = 'Toko baru didaftarkan & Laporan tersimpan!';
        } else {
            $customerId = $request->customer_id;
            $msg = 'Laporan kunjungan berhasil disimpan!';
        }

        // 2. Upload Foto
        $photoPath = $request->file('photo')->store('visits', 'public');

        // 3. Simpan Visit
        // Kita kondisikan lat/long dan visit_type berdasarkan role
        Visit::create([
            'user_id'        => $user->id,
            'customer_id'    => $customerId,
            'visit_date'     => now(),
            'status'         => 'completed',
            'completed_at'   => now(),
            'check_out_time' => now(),

            // Jika Sales Store: GPS null. Jika Sales Lapangan: Ambil dari request
            'latitude'       => $isStoreSales ? null : $request->latitude,
            'longitude'      => $isStoreSales ? null : $request->longitude,

            // Penanda Tipe Kunjungan (Penting untuk filter laporan nanti)
            'visit_type'     => $isStoreSales ? 'store' : 'field',

            'photo_path'     => $photoPath,
            'notes'          => $request->notes,
        ]);

        return redirect()->route('dashboard')->with('success', $msg);
    }

    // ==========================================================
    // 2. FITUR MONITORING (INDEX & TARGET)
    // ==========================================================

    public function index(Request $request)
    {
        $user = Auth::user();

        // --- A. PERSIAPAN FILTER (Tanggal & User) ---
        // Default: Hari ini jika tidak ada filter tanggal
        $startDate = $request->start_date ?? date('Y-m-d');
        $endDate = $request->end_date ?? date('Y-m-d');

        // Base Query: Query dasar yang belum dieksekusi
        $baseQuery = Visit::with(['user', 'customer'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->latest();

        // Filter akses berdasarkan Role
        if (in_array($user->role, ['sales', 'sales_field', 'sales_store'])) {
            // Sales hanya lihat punya sendiri
            $baseQuery->where('user_id', $user->id);
        } elseif ($request->sales_id) {
            // Manager bisa filter spesifik sales
            $baseQuery->where('user_id', $request->sales_id);
        }

        // --- B. EKSEKUSI PEMISAHAN DATA (FIELD vs STORE) ---
        // Kita clone query agar tidak saling menimpa
        $fieldVisits = (clone $baseQuery)->where('visit_type', 'field')->get();
        $storeVisits = (clone $baseQuery)->where(function ($q) {
            $q->where('visit_type', 'store')
                ->orWhereNull('visit_type'); // Antisipasi data lama yg null
        })->get();

        // Hitung Ringkasan untuk Dashboard Mini
        $summary = [
            'total_all'   => $fieldVisits->count() + $storeVisits->count(),
            'total_field' => $fieldVisits->count(),
            'total_store' => $storeVisits->count(),
        ];

        // --- C. LOGIKA REKAP BULANAN (Target vs Actual) ---
        // Logika ini tetap dipertahankan namun query user diperluas untuk sales_store
        $monthlyRecap = [];

        // Hanya Manager yang butuh kalkulasi berat ini
        if (in_array($user->role, ['manager_operasional', 'manager_bisnis'])) {

            // Ambil semua tipe sales (lapangan & toko)
            $allSales = \App\Models\User::whereIn('role', ['sales', 'sales_field', 'sales_store'])->get();
            $workingDays = 25;

            foreach ($allSales as $sales) {
                // 1. Hitung Visit (Bulan Ini)
                $actualVisits = Visit::where('user_id', $sales->id)
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->where('status', 'completed')
                    ->count();

                $dailyTarget = $sales->daily_visit_target ?? 5;
                $monthlyTarget = $dailyTarget * $workingDays;
                $visitAchievement = $monthlyTarget > 0 ? ($actualVisits / $monthlyTarget) * 100 : 0;

                // 2. Hitung Omset (Bulan Ini)
                $currentOmset = \App\Models\Order::where('user_id', $sales->id)
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->sum('total_price');

                $targetOmset = $sales->sales_target ?? 0; // Pastikan kolom ini ada di tabel users
                $omsetPercentage = $targetOmset > 0 ? ($currentOmset / $targetOmset) * 100 : 0;

                $monthlyRecap[] = [
                    'id' => $sales->id,
                    'name' => $sales->name,
                    'role' => $sales->role, // Tambahan info role
                    'daily_target' => $dailyTarget,
                    'monthly_visit_target' => $monthlyTarget,
                    'actual_visit' => $actualVisits,
                    'visit_percentage' => round($visitAchievement, 1),
                    'current_omset' => $currentOmset,
                    'target_omset' => $targetOmset,
                    'omset_percentage' => round($omsetPercentage, 1),
                ];
            }
        }

        // --- D. KIRIM KE VIEW ---
        // Variable salesList untuk dropdown filter di view
        $salesList = \App\Models\User::whereIn('role', ['sales', 'sales_field', 'sales_store'])->get();

        return view('visits.index', compact(
            'fieldVisits',
            'storeVisits',
            'summary',
            'monthlyRecap',
            'salesList'
        ));
    }

    // Update Target Sales (Kunjungan & Omset)
    public function updateTarget(Request $request)
    {
        if (!in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis'])) {
            abort(403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'daily_visit_target' => 'required|integer|min:1',
            // Validasi dihapus dulu 'numeric'-nya biar gak error kalau ada titik
            'sales_target' => 'required',
        ]);

        $sales = \App\Models\User::find($request->user_id);

        // BERSIHKAN TITIK/KOMA DARI INPUTAN RUPIAH
        // Ubah "50.000.000" menjadi "50000000"
        $cleanTarget = str_replace(['.', ','], '', $request->sales_target);

        $sales->update([
            'daily_visit_target' => $request->daily_visit_target,
            'sales_target' => $cleanTarget // Simpan angka bersih
        ]);

        return back()->with('success', 'Target berhasil diperbarui!');
    }

    // ==========================================================
    // 3. FITUR PERENCANAAN & EKSEKUSI (CHECK-IN -> WAIT -> CHECK-OUT)
    // ==========================================================

    public function createPlan()
    {
        $query = Customer::query();

        if (Auth::user()->role === 'sales') {
            $query->where('user_id', Auth::id());
        }

        $customers = $query->orderBy('name')->get();

        return view('visits.plan', compact('customers'));
    }

    public function storePlan(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'visit_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        Visit::create([
            'user_id' => Auth::id(),
            'customer_id' => $request->customer_id,
            'visit_date' => $request->visit_date,
            'status' => 'planned',
            'notes' => $request->notes,
        ]);

        return redirect()->route('dashboard')->with('success', 'Rencana kunjungan berhasil dibuat!');
    }
    // BARU: Method untuk tombol "Check In" di Dashboard
    public function checkIn($id)
    {
        $visit = Visit::findOrFail($id);

        // Validasi: Cuma boleh check-in kalau status masih 'planned'
        if ($visit->status !== 'planned') {
            return back()->with('error', 'Status kunjungan tidak valid untuk Check-in.');
        }

        // Update Data
        $visit->update([
            'status' => 'in_progress', // Status baru: Sedang Berjalan
            'check_in_time' => Carbon::now(), // Waktu Start Argo
        ]);

        return back()->with('success', 'Berhasil Check-in! Waktu kunjungan dimulai.');
    }
    // 1. FUNGSI MEMBUKA HALAMAN LAPORAN (PERFORM)
    public function perform($id)
    {
        $visit = Visit::findOrFail($id);

        // CEK DURASI SEBELUM BUKA FORM
        // Jika belum 20 menit, tolak sales buka halaman ini
        $durasiBerjalan = $visit->created_at->diffInMinutes(now());

        // Ganti angka 20 sesuai kebutuhan minimal menit
        if ($durasiBerjalan < 20) {
            $sisaWaktu = 20 - $durasiBerjalan;
            $waktuSisa = round($sisaWaktu);
            return back()->with('error', "Belum bisa Check Out! Minimal kunjungan 20 menit. Sisa waktu: $waktuSisa menit.");
        }

        // Jika sudah > 20 menit, tampilkan halaman form
        return view('visits.perform', compact('visit'));
    }

    // 2. FUNGSI MENYIMPAN DATA (UPDATE) - INI YANG DI AKSES TOMBOL DI HALAMAN FORM
    public function update(Request $request, $id)
    {
        $visit = Visit::findOrFail($id);

        // Validasi Input (Wajib Foto & Lokasi)
        $request->validate([
            'photo' => 'required|image|max:5120', // Maks 5MB
            'latitude' => 'required',
            'longitude' => 'required',
            'notes' => 'nullable|string',
        ]);

        // Proses Upload Foto
        $path = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('visit-proofs', 'public');
        }

        // Simpan Data
        $visit->update([
            'check_out_time' => now(),
            'photo_path' => $path,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'notes' => $request->notes,
            'status' => 'completed',
        ]);

        return redirect()->route('dashboard')->with('success', 'Kunjungan Selesai! Terima kasih.');
    }
    // ==========================================================
    // 4. FITUR DETAIL (SHOW) - Agar Route Resource Tidak Error
    // ==========================================================
    public function show($id)
    {
        // Cari data visit beserta relasinya
        $visit = Visit::with(['user', 'customer'])->findOrFail($id);

        // Tampilkan view detail (jika ada), atau return JSON sementara
        // return view('visits.show', compact('visit')); // Aktifkan jika sudah buat file view-nya

        // Sementara kita redirect kembali saja atau tampilkan data mentah
        return response()->json($visit);
    }
}
