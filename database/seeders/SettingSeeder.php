<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\CustomerCategory;
use App\Models\Setting;
use App\Models\Gudang;
use App\Models\Gate;
use App\Models\Block;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // =========================================================
        // 1. KATEGORI PRODUK
        // =========================================================
        // $cats = [
        //     'Wallpaper Dinding',
        //     'Vinyl Flooring',
        //     'SPC Flooring',
        //     'Parket Kayu',
        //     'Keramik Lantai',
        //     'Keramik Dinding',
        //     'Granit Alam',
        //     'Carpet Tile',
        //     'Lem & Aksesoris',
        //     'Wall Panel (WPC)',
        //     'Gorden / Blinds',
        // ];

        // foreach ($cats as $c) {
        //     Category::firstOrCreate(['name' => $c]);
        // }

        // =========================================================
        // 2. KATEGORI PELANGGAN
        // =========================================================
        $customerCats = [
            'Customer',      // Pembeli perorangan
            'Kontraktor',           // Pemborong proyek
            'Workshop',  // Bengkel interior
            'Studio Design',        // Konsultan/Arsitek
            'Furniture',
            'Toko / Reseller', // Toko bangunan lain
        ];

        foreach ($customerCats as $cc) {
            CustomerCategory::firstOrCreate(['name' => $cc]);
        }

        // =========================================================
        // 3. PENGATURAN UMUM APLIKASI
        // =========================================================
        $settings = [
            'app_name'        => 'Sales App',
            'company_name'    => 'PT. Bintang Interior & Keramik',
            'company_address' => 'Jl. Teuku Iskandar, Ceurih, Kec. Ulee Kareng, Kota Banda Aceh, Aceh 23117',
            'company_phone'   => '0823-8617-0553',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // // =========================================================
        // // 4. LOKASI GUDANG (DATA DUMMY UNTUK DEMO)
        // // =========================================================

        // // A. Buat Gudang Pusat
        // $gudangPusat = Gudang::firstOrCreate(
        //     ['name' => 'Gudang Pusat (Lambaro)'],
        // );

        // // B. Buat Gate (Pintu/Area) di Gudang Pusat
        // // Gate 1: Area Berat (Keramik/Granit)
        // $gate1 = Gate::firstOrCreate(
        //     ['name' => 'Gate A (Material Berat)', 'gudang_id' => $gudangPusat->id]
        // );
        // // Blok di Gate 1
        // Block::firstOrCreate(['name' => 'Blok A1 (Granit 60x60)', 'gate_id' => $gate1->id]);
        // Block::firstOrCreate(['name' => 'Blok A2 (Keramik Lantai)', 'gate_id' => $gate1->id]);
        // Block::firstOrCreate(['name' => 'Blok A3 (Keramik Dinding)', 'gate_id' => $gate1->id]);

        // // Gate 2: Area Interior (Vinyl/Wallpaper)
        // $gate2 = Gate::firstOrCreate(
        //     ['name' => 'Gate B (Interior & Finishing)', 'gudang_id' => $gudangPusat->id]
        // );
        // // Blok di Gate 2
        // Block::firstOrCreate(['name' => 'Blok B1 (Vinyl & SPC)', 'gate_id' => $gate2->id]);
        // Block::firstOrCreate(['name' => 'Blok B2 (Wallpaper)', 'gate_id' => $gate2->id]);
        // Block::firstOrCreate(['name' => 'Blok B3 (Lem & Aksesoris)', 'gate_id' => $gate2->id]);
    }
}
