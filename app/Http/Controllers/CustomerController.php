<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\CustomerCategory;

class CustomerController extends Controller
{
    // 1. Tampilkan Daftar Toko
    public function index(Request $request)
    {
        // 1. Ambil List Sales & Kategori untuk Dropdown
        $salesList = User::where('role', 'sales')->orderBy('name')->get();

        // Ambil kategori unik dari database (agar dropdown otomatis terisi sesuai data yg ada)
        // Pastikan kolom 'category' ada di tabel customers ya!
        $categories = Customer::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category');

        // 2. Mulai Query
        $query = Customer::with(['user']);

        // 3. Filter Role & Sales (Kode Lama)
        if (Auth::user()->role === 'sales') {
            $query->where('user_id', Auth::id());
        } else {
            if ($request->has('sales_id') && $request->sales_id != '') {
                $query->where('user_id', $request->sales_id);
            }
        }

        // 4. FILTER KATEGORI
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        // 5. Pencarian (Yang tadi sudah diperbaiki)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('contact_person', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        // 6. Ambil Data
        $customers = $query->latest()->paginate(10);
        $customers->appends($request->all()); // Agar filter tidak hilang saat ganti halaman

        return view('customers.index', compact('customers', 'salesList', 'categories'));
    }

    // 2. Form Tambah Toko (Bisa diakses Sales)
    public function create()
    {
        $categories = CustomerCategory::all();

        return view('customers.create', compact('categories'));
    }

    // 3. Simpan Toko Baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'category' => 'required|string',
            'top_days' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|string',   // Tambahkan ini
            'longitude' => 'nullable|string',  // Tambahkan ini
        ]);

        Customer::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'address' => $request->address,
            'category' => $request->category,
            // FIELD BARU
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,

            // SET DEFAULT (Karena sudah dipindah ke Transaksi)
            'top_days' => 0,
            'credit_limit' => 0,
        ]);

        return redirect()->route('customers.index')->with('success', 'Toko berhasil didaftarkan!');
    }

    // 4. Form Edit (Admin Only)
    public function edit(Customer $customer)
    {
        $categories = CustomerCategory::all();
        return view('customers.edit', compact('customer', 'categories'));
    }

    // 5. Update Data
    public function update(Request $request, Customer $customer)
    {
        // 1. VALIDASI (Sesuaikan dengan Form Edit yang baru)
        $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'required',
            'address'    => 'required',
            'category'   => 'required|string',

            // TAMBAHAN WAJIB: Agar Koordinat Maps bisa disimpan
            'latitude'   => 'nullable|string',
            'longitude'  => 'nullable|string',
        ]);

        // 2. AMBIL DATA INPUTAN BARU
        // Kita buang 'top_days' & 'credit_limit' dari sini karena sudah tidak dipakai di form update
        $newData = $request->only([
            'name',
            'email',
            'phone',
            'address',
            'category',
            'latitude',  // <-- Masukkan ini
            'longitude'  // <-- Masukkan ini
        ]);

        // --- LOGIKA APPROVAL SALES (TETAP ADA) ---
        // Jika yang edit BUKAN Manager, harus lewat persetujuan
        if (!in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional'])) {

            // Cek apakah ada perbedaan data (termasuk koordinat)?
            // Kita bandingkan input baru vs data lama di database
            $currentData = $customer->only(array_keys($newData));
            $diff = array_diff_assoc($newData, $currentData);

            if (empty($diff)) return back()->with('info', 'Tidak ada perubahan data.');

            // Buat Tiket Approval
            \App\Models\Approval::create([
                'model_type'    => \App\Models\Customer::class,
                'model_id'      => $customer->id,
                'action'        => 'update_customer', // Action khusus update customer
                'original_data' => $currentData,      // Simpan data lama
                'new_data'      => $newData,          // Simpan data baru (termasuk koordinat)
                'status'        => 'pending',
                'requester_id'  => Auth::id(),
            ]);

            return redirect()->route('customers.index')
                ->with('success', 'Permintaan perubahan Lokasi/Data Toko dikirim ke Manager.');
        }

        // --- JIKA MANAGER, LANGSUNG EKSEKUSI ---
        $customer->update($newData);

        return redirect()->route('customers.index')->with('success', 'Data Customer & Lokasi berhasil diperbarui.');
    }
    // 6. Hapus Data (Soft Delete)
    public function destroy(Customer $customer)
    {
        // LOGIKA APPROVAL: Jika yang hapus BUKAN Manager, harus izin dulu
        if (!in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional'])) {

            // Cek duplikasi request hapus
            $isPending = \App\Models\Approval::where('model_type', \App\Models\Customer::class)
                ->where('model_id', $customer->id)
                ->where('action', 'delete')
                ->where('status', 'pending')
                ->exists();

            if ($isPending) {
                return back()->with('warning', 'Permintaan hapus sedang menunggu persetujuan.');
            }

            // Buat Tiket Approval
            \App\Models\Approval::create([
                'model_type'    => \App\Models\Customer::class,
                'model_id'      => $customer->id,
                'action'        => 'delete',
                'original_data' => $customer->toArray(),
                'new_data'      => null,
                'status'        => 'pending',
                'requester_id'  => Auth::id(),
            ]);

            return redirect()->route('customers.index')
                ->with('success', 'Permintaan hapus dikirim ke Manager.');
        }

        // --- KHUSUS MANAGER ---
        // Karena sudah SoftDeletes, perintah ini AMAN.
        // Data SO/Order tidak akan hilang, Customer hanya 'disembunyikan'.
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Toko berhasil dinonaktifkan (Soft Delete).');
    }
    public function listTop()
    {
        // Ambil customer yang punya limit kredit (TOP Active)
        $customers = Customer::where('credit_limit', '>', 0)
            ->orderBy('name')
            ->get();

        return view('customers.top_list', compact('customers'));
    }
}
