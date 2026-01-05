<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Customer;
use App\Models\Approval; // Pastikan model Approval diimport jika dipakai
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VisitController extends Controller
{
    /**
     * Helper: Validasi Keamanan File Ekstra Cepat
     * Mencegah file script berbahaya (.php, .exe, dll) masuk ke processing image
     */
    private function validateFileSafety($request)
    {
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');

            // 1. Cek Ekstensi Berbahaya (Blacklist) - Sangat Cepat
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
        return null; // Aman
    }

    public function create()
    {
        $user = Auth::user();

        if (in_array($user->role, ['sales_store', 'sales_field'])) {
            $customers = \App\Models\Customer::where('user_id', $user->id)
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $customers = \App\Models\Customer::orderBy('name', 'asc')->get();
        }

        // Pastikan Model Category sesuai aplikasi Anda
        $categories = \App\Models\CustomerCategory::all();
        return view('visits.create', compact('customers', 'categories'));
    }

    public function store(Request $request)
    {
         // --- LAYER 1: SECURITY CHECK (MANUAL & CEPAT) ---
        // Ini akan menendang file .php SEBELUM masuk ke validasi 'image' Laravel yang berat
        if ($error = $this->validateFileSafety($request)) {
            return back()->withErrors(['photo' => $error])->withInput();
        }

        // --- LAYER 2: LARAVEL VALIDATION ---
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        $user = Auth::user();
        $isStoreSales = $user->role === 'sales_store';

        $messages = [
            'photo.required'    => 'Wajib ambil foto kunjungan/toko.',
            'photo.max'         => 'Ukuran foto terlalu besar (maks 5MB).',
            'latitude.required' => 'Gagal mendeteksi lokasi GPS.',
            'notes.required'    => 'Catatan hasil kunjungan wajib diisi.',
            'check_out_time.after' => 'Jam selesai harus lebih akhir dari jam mulai.',
            'new_name.required' => 'Nama toko baru wajib diisi.',
            'customer_id.required' => 'Silakan pilih customer dari daftar.',
        ];

        $rules = [
            'type' => 'required|in:existing,new',
            'notes' => 'required|string',
        ];

        if (!$isStoreSales) {
            $rules['latitude']  = 'required';
            $rules['longitude'] = 'required';
        } else {
            $rules['check_in_time']  = 'required|date';
            $rules['check_out_time'] = 'required|date|after:check_in_time';
        }

        if ($request->type == 'new') {
            $rules['new_name']     = 'required|string|max:255';
            $rules['new_phone']    = 'required|string|max:20';
            $rules['new_address']  = 'required|string';
            $rules['new_category'] = 'required|string';
        } else {
            $rules['customer_id'] = 'required|exists:customers,id';
        }

        $request->validate($rules, $messages);

        if ($isStoreSales) {
            $checkIn  = \Carbon\Carbon::parse($request->check_in_time);
            $checkOut = \Carbon\Carbon::parse($request->check_out_time);
        } else {
            $checkIn  = now()->subMinutes(20);
            $checkOut = now();
        }

        $duration = abs($checkIn->diffInMinutes($checkOut));

        if ($isStoreSales && $duration < 20) {
            return back()
                ->withInput()
                ->withErrors(['check_out_time' => 'Durasi pelayanan minimal 20 menit.']);
        }

        // Simpan Customer Baru / Ambil ID
        if ($request->type == 'new') {
            $newCustomer = \App\Models\Customer::create([
                'user_id'        => $user->id,
                'name'           => $request->new_name,
                'phone'          => $request->new_phone,
                'address'        => $request->new_address,
                'category'       => $request->new_category,
                'contact_person' => $request->new_contact ?? null,
                'credit_limit'   => 0,
                'status'         => 'pending_approval',
            ]);

            \App\Models\Approval::create([
                'requester_id' => $user->id,
                'model_type'   => \App\Models\Customer::class,
                'model_id'     => $newCustomer->id,
                'action'       => 'create',
                'status'       => 'pending',
                'details'      => json_encode(['reason' => 'Customer Baru dari Sales Store']),
                'data'         => $newCustomer->toArray()
            ]);

            $customerId = $newCustomer->id;
        } else {
            $customerId = $request->customer_id;
        }

        // --- LAYER 3: SAFE STORAGE ---
        try {
            $photoPath = $request->file('photo')->store('visits', 'public');

            \App\Models\Visit::create([
                'user_id'          => $user->id,
                'customer_id'      => $customerId,
                'visit_date'       => $checkIn->format('Y-m-d'),
                'check_in_at'      => $checkIn,
                'check_out_at'     => $checkOut,
                'duration_minutes' => $duration,
                'status'           => 'completed',
                'latitude'         => $isStoreSales ? null : $request->latitude,
                'longitude'        => $isStoreSales ? null : $request->longitude,
                'visit_type'       => $isStoreSales ? 'store' : 'field',
                'photo_path'       => $photoPath,
                'notes'            => $request->notes,
            ]);

            return redirect()->route('dashboard')->with('success', 'Laporan kunjungan berhasil disimpan!');
        } catch (\Exception $e) {
            return back()->withErrors(['photo' => 'Upload failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isManager = in_array($user->role, ['manager_operasional', 'manager_bisnis']);
        $startDate = $request->start_date ?? date('Y-m-d');
        $endDate   = $request->end_date ?? date('Y-m-d');

        $baseQuery = \App\Models\Visit::with(['user', 'customer'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->latest();

        if (!$isManager) {
            $baseQuery->where('user_id', $user->id);
        } elseif ($request->sales_id) {
            $baseQuery->where('user_id', $request->sales_id);
        }

        $fieldVisits = (clone $baseQuery)->where('visit_type', 'field')->get();
        $storeVisits = (clone $baseQuery)->where(function ($q) {
            $q->where('visit_type', 'store')->orWhereNull('visit_type');
        })->get();

        $summary = [
            'total_all'   => $fieldVisits->count() + $storeVisits->count(),
            'total_field' => $fieldVisits->count(),
            'total_store' => $storeVisits->count(),
        ];

        $monthlyRecap = [];
        $salesList = [];

        if ($isManager) {
            $salesList = \App\Models\User::whereIn('role', ['sales_field', 'sales_store'])->get();
            // Logic rekap bulanan disederhanakan agar tidak error jika model Order/User beda
            // ... (Kode asli Anda untuk rekap bisa ditaruh di sini jika perlu)
        }

        return view('visits.index', compact('fieldVisits', 'storeVisits', 'summary', 'monthlyRecap', 'salesList', 'startDate', 'endDate'));
    }

    public function updateTarget(Request $request)
    {
        if (!in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis'])) {
            abort(403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'daily_visit_target' => 'required|integer|min:1',
            'sales_target' => 'required',
        ]);

        $sales = \App\Models\User::find($request->user_id);
        $cleanTarget = str_replace(['.', ','], '', $request->sales_target);

        $sales->update([
            'daily_visit_target' => $request->daily_visit_target,
            'sales_target' => $cleanTarget
        ]);

        return back()->with('success', 'Target berhasil diperbarui!');
    }

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

    public function checkIn($id)
    {
        $visit = Visit::findOrFail($id);
        if ($visit->status !== 'planned') {
            return back()->with('error', 'Status kunjungan tidak valid untuk Check-in.');
        }
        $visit->update([
            'status' => 'in_progress',
            'check_in_time' => Carbon::now(),
        ]);
        return back()->with('success', 'Berhasil Check-in! Waktu kunjungan dimulai.');
    }

    public function perform($id)
    {
        $visit = Visit::findOrFail($id);
        $durasiBerjalan = $visit->created_at->diffInMinutes(now());

        if ($durasiBerjalan < 20) {
            $sisaWaktu = 20 - $durasiBerjalan;
            return back()->with('error', "Belum bisa Check Out! Minimal kunjungan 20 menit. Sisa waktu: " . round($sisaWaktu) . " menit.");
        }
        return view('visits.perform', compact('visit'));
    }

    // --- METHOD INI YANG SERING JADI MASALAH SAAT TESTING ---
    public function update(Request $request, $id)
    {
        dd('MASUK METHOD UPDATE'); // <--- TAMBAHKAN INI
        $visit = \App\Models\Visit::findOrFail($id);

        // --- LAYER 1: SECURITY CHECK (MANUAL & CEPAT) ---
        if ($error = $this->validateFileSafety($request)) {
            return back()->withErrors(['photo' => $error])->withInput();
        }

        // --- LAYER 2: LARAVEL VALIDATION ---
        $request->validate([
            'photo'     => ['required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'latitude'  => 'required',
            'longitude' => 'required',
            'notes'     => 'nullable|string',
        ], [
            'photo.required' => 'Foto bukti selesai kunjungan wajib ada.',
            'photo.image'    => 'File harus berupa gambar.',
        ]);

        // --- LAYER 3: SAFE STORAGE ---
        try {
            $path = null;
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('visit-proofs', 'public');
            }

            $visit->update([
                'check_out_time' => now(),
                'photo_path'     => $path,
                'latitude'       => $request->latitude,
                'longitude'      => $request->longitude,
                'notes'          => $request->notes,
                'status'         => 'completed',
            ]);

            return redirect()->route('dashboard')->with('success', 'Kunjungan selesai. Data tersimpan. ✅');

        } catch (\Exception $e) {
            return back()->withErrors(['photo' => 'Upload failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function show($id)
    {
        $visit = Visit::with(['user', 'customer'])->findOrFail($id);
        return response()->json($visit);
    }
}
