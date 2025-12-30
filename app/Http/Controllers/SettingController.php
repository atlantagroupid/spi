<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Setting;
use App\Models\CustomerCategory;

class SettingController extends Controller
{
    // TAMPILKAN HALAMAN SETTING
    public function index()
    {
        $categories = Category::all();
        $customerCategories = CustomerCategory::all();

        // Ambil settingan jadi array biar gampang dipanggil (key => value)
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('settings.index', compact('categories', 'settings', 'customerCategories'));
    }

    // SIMPAN SETTING UMUM
    public function updateGeneral(Request $request)
    {
        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Pengaturan situs berhasil diperbarui!');
    }

    // TAMBAH KATEGORI
    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|unique:categories,name']);
        Category::create(['name' => $request->name]);
        return back()->with('success', 'Kategori baru berhasil ditambahkan!');
    }

    // HAPUS KATEGORI
    public function destroyCategory($id)
    {
        Category::destroy($id);
        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    // TAMBAH KATEGORI CUSTOMER
    public function storeCustomerCategory(Request $request)
    {
        $request->validate(['name' => 'required|unique:customer_categories,name']);
        CustomerCategory::create(['name' => $request->name]);
        return back()->with('success', 'Kategori Customer berhasil ditambahkan!');
    }

    // HAPUS KATEGORI CUSTOMER
    public function destroyCustomerCategory($id)
    {
        CustomerCategory::destroy($id);
        return back()->with('success', 'Kategori Customer berhasil dihapus.');
    }
}
