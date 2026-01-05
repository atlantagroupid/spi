<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Gudang;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasImageUpload; // Aktifkan jika Anda benar-benar punya file Trait ini

class ProductController extends Controller
{
    use HasImageUpload;

    // --- HELPER VALIDASI KEAMANAN (Optional untuk Produk, tapi Bagus) ---
    private function validateFileSafety(Request $request, $fieldName = 'image')
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            $blocked = ['php', 'exe', 'sh', 'bat'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $blocked)) return 'File berbahaya terdeteksi.';

            // Produk boleh WEBP
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) return 'Hanya format JPG, PNG, dan WEBP.';
        }
        return null;
    }

    public function index(Request $request)
    {
        $lowStockProducts = Product::where('stock', '<=', 50)->orderBy('stock', 'asc')->paginate(5, ['*'], 'alert_page');
        $query = Product::query();

        if ($request->filled('search')) $query->where('name', 'like', '%' . $request->search . '%');
        if ($request->filled('category')) $query->where('category', $request->category);

        if ($request->stock_status == 'out') $query->where('stock', 0);
        elseif ($request->stock_status == 'low') $query->where('stock', '<=', 50)->where('stock', '>', 0);
        elseif ($request->stock_status == 'safe') $query->where('stock', '>', 50);

        if ($request->is_discount == '1') $query->whereNotNull('discount_price');

        $totalAsset = Product::sum(DB::raw('price * stock'));
        $totalStock = Product::sum('stock');
        $products = $query->paginate(10)->withQueryString();
        $categories = Product::select('category')->distinct()->pluck('category');

        return view('products.index', compact('products', 'categories', 'lowStockProducts', 'totalAsset', 'totalStock'));
    }

    public function create()
    {
        // Pastikan Model Category ada, jika error ganti jadi array manual dulu
        $categories = \App\Models\Category::orderBy('name')->pluck('name');
        $gudangs = Gudang::orderBy('name')->get();
        return view('products.create', compact('categories', 'gudangs'));
    }

    public function store(Request $request)
    {
        // 1. Security Check Manual
        if ($error = $this->validateFileSafety($request, 'image')) {
            return back()->withErrors(['image' => $error])->withInput();
        }

        $messages = [
            'name.required' => 'Nama produk wajib diisi.',
            'name.unique'   => 'Nama produk ini sudah ada di sistem.',
            'image.max'     => 'Ukuran foto maksimal 2MB.',
            'image.image'   => 'File harus berupa gambar.',
            'image.mimes'   => 'Format harus JPG, PNG, atau WEBP.',
        ];

        $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'category' => 'required',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'lokasi_gudang' => 'nullable',
            'gate' => 'nullable',
            'block' => 'nullable',
            'description' => 'nullable',
            // UBAH 'photo' JADI 'image' AGAR SESUAI FORM
            'image' => [
                'required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048',
            ],
        ], $messages);

        $newData = $request->except('_token');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = $image->hashName();
            $image->storeAs('products', $filename, 'public');
            $newData['image'] = $filename;
        }

        Approval::create([
            'model_type' => Product::class,
            'model_id' => null,
            'action' => 'create',
            'original_data' => null,
            'new_data' => $newData,
            'status' => 'pending',
            'requester_id' => Auth::id(),
        ]);

        return redirect()->route('products.index')->with('success', 'Permintaan tambah produk dikirim.');
    }

    public function edit(Product $product)
    {
        $categories = \App\Models\Category::orderBy('name')->pluck('name');
        $gudangs = Gudang::orderBy('name')->get();
        return view('products.edit', compact('product', 'categories', 'gudangs'));
    }

    public function update(Request $request, Product $product)
    {
        // 1. Security Check Manual
        if ($error = $this->validateFileSafety($request, 'image')) {
            return back()->withErrors(['image' => $error])->withInput();
        }

        $request->validate([
            'name' => 'required',
            'category' => 'required',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'stock' => 'required|integer|min:0',
            'lokasi_gudang' => 'nullable',
            'gate' => 'nullable',
            'block' => 'nullable',
            'description' => 'nullable',
            // UBAH 'photo' JADI 'image' & NULLABLE (karena update)
            'image' => [
                'nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048',
            ],
        ]);

        $newData = $request->only(['name', 'category', 'price', 'stock', 'lokasi_gudang', 'gate', 'block', 'description']);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = $image->hashName();
            $image->storeAs('products', $filename, 'public');
            $newData['image'] = $filename;
        }

        // Logic Approval Admin Gudang
        if (Auth::user()->role === 'admin_gudang') {
            $diff = array_diff_assoc($newData, $product->only(array_keys($newData)));
            if (empty($diff)) return redirect()->route('products.index')->with('info', 'Tidak ada data berubah.');

            Approval::create([
                'model_type' => Product::class,
                'model_id' => $product->id,
                'action' => 'update',
                'original_data' => $product->toArray(),
                'new_data' => $newData,
                'status' => 'pending',
                'requester_id' => Auth::id(),
            ]);

            return redirect()->route('products.index')->with('success', 'Permintaan edit dikirim.');
        }

        // Direct Update (Manager)
        if ($request->hasFile('image') && $product->image) {
            Storage::disk('public')->delete('products/' . $product->image);
        }

        $product->update($newData);
        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function destroy(Product $product)
    {
        if (Auth::user()->role === 'admin_gudang') {
            Approval::create([
                'model_type' => Product::class,
                'model_id' => $product->id,
                'action' => 'delete',
                'original_data' => $product->toArray(),
                'new_data' => null,
                'status' => 'pending',
                'requester_id' => Auth::id(),
            ]);
            return redirect()->route('products.index')->with('success', 'Permintaan hapus dikirim.');
        }

        if ($product->image) Storage::disk('public')->delete('products/' . $product->image);
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk dihapus.');
    }

    public function updateRestock(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['purchase', 'manager_operasional', 'manager_bisnis', 'kepala_gudang'])) abort(403);

        $request->validate(['restock_date' => 'required|date|after_or_equal:today']);
        Product::findOrFail($id)->update(['restock_date' => $request->restock_date]);

        return back()->with('success', 'Tanggal restock diupdate.');
    }

    public function updateDiscount(Request $request, $id)
    {
        if (Auth::user()->role !== 'purchase') abort(403);

        $request->validate(['discount_price' => 'nullable|numeric|min:0']);
        $product = Product::findOrFail($id);
        $product->update(['discount_price' => $request->discount_price > 0 ? $request->discount_price : null]);

        return back()->with('success', 'Diskon diupdate.');
    }
}
