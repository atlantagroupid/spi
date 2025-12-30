<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Gudang;
use App\Models\Product; // Jangan lupa import Model ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\HasImageUpload;

class ProductController extends Controller
{
    use HasImageUpload; // 2. Aktifkan Trait di dalam class
    // Menampilkan Daftar Produk
    public function index(Request $request)
    {
        // 1. Ambil data stok menipis (untuk tabel atas khusus Purchase/Manager)
        $lowStockProducts = Product::where('stock', '<=', 49)->get();

        // 2. Query Utama untuk Tabel Bawah
        $query = Product::query();

        // Filter Pencarian Nama
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter Kategori
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter Status Stok
        if ($request->stock_status == 'out') {
            $query->where('stock', 0);
        } elseif ($request->stock_status == 'low') {
            $query->where('stock', '<=', 50)->where('stock', '>', 0);
        } elseif ($request->stock_status == 'safe') {
            $query->where('stock', '>', 50);
        }

        // Kita cek dulu: Apakah user memilih filter "Sedang Diskon"?
        if ($request->is_discount == '1') {
            // Jika YA, suruh $query cari yang harga diskonnya TIDAK KOSONG
            $query->whereNotNull('discount_price');
        }

        // Hitung Total Nilai Aset (Harga * Stok semua produk)
        $totalAsset = Product::sum(DB::raw('price * stock'));

        // Hitung Total Jumlah Barang (Unit)
        $totalStock = Product::sum('stock');

        $products = $query->paginate(10)->withQueryString();

        // Ambil list kategori unik untuk dropdown
        $categories = Product::select('category')->distinct()->pluck('category');

        return view('products.index', compact('products', 'categories', 'lowStockProducts', 'totalAsset', 'totalStock'));
    }

    // 1. Tampilkan Form Tambah Produk
    public function create()
    {
        // Ambil kategori dari database, bukan manual array lagi
        $categories = \App\Models\Category::orderBy('name')->pluck('name');
        $gudangs = Gudang::orderBy('name')->get();

        return view('products.create', compact('categories', 'gudangs'));
    }

    // 2. Proses Simpan Data ke Database
    public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'category' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'lokasi_gudang' => 'nullable',
            'gate' => 'nullable',
            'block' => 'nullable',
            'description' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Validasi foto
        ]);

        // 2. Siapkan Data Input
        $newData = $request->except('_token');

        // 3. Cek apakah user mengupload foto?
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = $image->hashName();
            $image->storeAs('products', $filename, 'public');

            // Simpan nama file ke array data baru
            $newData['image'] = $filename;
        }

        // 4. Buat Tiket Approval untuk produk baru
        Approval::create([
            'model_type' => Product::class,
            'model_id' => null, // Produk belum ada, jadi ID-nya null
            'action' => 'create',
            'original_data' => null, // Tidak ada data lama
            'new_data' => $newData, // Data produk baru dari form
            'status' => 'pending',
            'requester_id' => Auth::id(),
        ]);

        return redirect()->route('products.index')->with('success', 'Permintaan tambah produk baru berhasil dikirim dan menunggu persetujuan.');
    }
    // 3. Tampilkan Form Edit (dengan data lama)
    public function edit(Product $product)
    {
        // Ambil kategori dari database
        $categories = \App\Models\Category::orderBy('name')->pluck('name');
        $gudangs = Gudang::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories', 'gudangs'));
    }

    public function update(Request $request, Product $product)
    {
        // 1. Validasi (Tambahkan validasi image)
        $request->validate([
            'name' => 'required',
            'category' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'lokasi_gudang' => 'nullable',
            'gate' => 'nullable',
            'block' => 'nullable',
            'description' => 'nullable',
            'image' => 'nullable|image|max:2048', // Cek validasi gambar
        ]);

        // 2. Ambil data teks dulu
        $newData = $request->only(['name', 'category', 'price', 'stock', 'lokasi_gudang', 'gate', 'block', 'description']);

        // 3. --- LOGIKA UPLOAD GAMBAR BARU (DISINI KITA SISIPKAN) ---
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = $image->hashName();

            // PENTING: Gunakan parameter 'public' agar masuk ke storage/app/public/products
            $image->storeAs('products', $filename, 'public');

            // Masukkan nama file baru ke array data yang akan diupdate/diapprove
            $newData['image'] = $filename;
        }

        // 4. --- LOGIKA APPROVAL (ADMIN GUDANG) ---
        // Jika yang edit adalah ADMIN GUDANG, harus lewat persetujuan
        if (Auth::user()->role === 'admin_gudang') {

            // Cek perbedaan data
            $diff = array_diff_assoc($newData, $product->only(array_keys($newData)));

            if (empty($diff)) {
                return redirect()->route('products.index')->with('info', 'Tidak ada data yang berubah.');
            }

            // Buat Tiket Approval
            \App\Models\Approval::create([
                'model_type' => \App\Models\Product::class,
                'model_id' => $product->id,
                'action' => 'update',
                'original_data' => $product->toArray(), // Data lama
                'new_data' => $newData,                 // Data baru (termasuk foto baru jika ada)
                'status' => 'pending',
                'requester_id' => Auth::id(),
            ]);

            return redirect()->route('products.index')
                ->with('success', 'Permintaan edit telah dikirim ke Manager untuk disetujui.');
        }

        // 5. --- LOGIKA DIRECT UPDATE (MANAGER/KEPALA GUDANG) ---
        // Jika bukan Admin Gudang, update langsung.

        // Cek: Kalau ada upload gambar baru, HAPUS GAMBAR LAMA biar server gak penuh
        if ($request->hasFile('image') && $product->image) {
            // Hapus file lama dari folder public/products
            \Illuminate\Support\Facades\Storage::disk('public')->delete('products/' . $product->image);
        }

        // Update data ke database
        $product->update($newData);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    // 5. Proses Hapus Data
    public function destroy(Product $product)
    {
        // --- LOGIKA APPROVAL HAPUS ---
        // Admin Gudang gak boleh hapus langsung
        if (Auth::user()->role === 'admin_gudang') {

            \App\Models\Approval::create([
                'model_type' => \App\Models\Product::class,
                'model_id' => $product->id,
                'action' => 'delete',
                'original_data' => $product->toArray(),
                'new_data' => null, // Hapus gak ada data baru
                'status' => 'pending',
                'requester_id' => Auth::id(),
            ]);

            // PERHATIKAN BAGIAN INI:
            return redirect()->route('products.index')
                ->with('success', 'Permintaan Hapus telah dikirim ke Manager untuk disetujui.');
            // ^^^ Kata-kata inilah yang akan muncul di Pop-up Cantik
        }

        // Role lain langsung hapus
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
    // METHOD BARU: Update Tanggal Restock (Khusus Purchase)
    public function updateRestock(Request $request, $id)
    {
        // Cek Role Purchase / Manager / Gudang
        if (!in_array(Auth::user()->role, ['purchase', 'manager_operasional', 'manager_bisnis', 'kepala_gudang'])) {
            abort(403);
        }

        $product = Product::findOrFail($id);
        $product->update([
            'restock_date' => $request->restock_date
        ]);

        return back()->with('success', 'Tanggal pemesanan stok berhasil diupdate.');
    }
    // METHOD BARU: Update Harga Diskon (Khusus Purchase)
    public function updateDiscount(Request $request, $id)
    {
        // Hanya Purchase yang boleh
        if (Auth::user()->role !== 'purchase') {
            abort(403, 'Akses ditolak. Hanya Purchase yang bisa atur diskon.');
        }

        $request->validate([
            'discount_price' => 'nullable|numeric|min:0'
        ]);

        $product = Product::findOrFail($id);

        // Simpan (Jika kosong/0, set null agar diskon mati)
        $product->update([
            'discount_price' => $request->discount_price > 0 ? $request->discount_price : null
        ]);

        return back()->with('success', 'Harga diskon berhasil diupdate.');
    }
}
