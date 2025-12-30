<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus user lama biar bersih (Opsional, hati-hati kalau data real)
        User::truncate();

        $password = Hash::make('password'); // Password sama semua biar gampang tes

        $users = [
            // 1. LEVEL TERTINGGI
            [
                'name' => 'Manager Operasional',
                'email' => 'manager_ops@bintang.com',
                'role' => 'manager_operasional', // Super Admin
                'sales_code' => null,
                'credit_limit_quota' => 0, // 0 tidak masalah karena dia adalah 'Pusat Otoritas'
            ],
            [
                'name' => ' Manager Bisnis',
                'email' => 'manager_biz@bintang.com',
                'role' => 'manager_bisnis', // Atasan Sales/Finance
                'sales_code' => null,
                'credit_limit_quota' => 500000000, // 500 JUTA (Modal Kolam untuk dibagikan ke Sales)
            ],

            // 2. DIVISI GUDANG (Limit 0 karena tidak jualan)
            [
                'name' => 'Pak Kepala Gudang',
                'email' => 'kepala_gudang@bintang.com',
                'role' => 'kepala_gudang',
                'sales_code' => null,
                'credit_limit_quota' => 0,
            ],
            [
                'name' => 'Staf Admin Gudang',
                'email' => 'admin_gudang@bintang.com',
                'role' => 'admin_gudang',
                'sales_code' => null,
                'credit_limit_quota' => 0,
            ],

            // 3. DIVISI LAIN (Limit 0)
            [
                'name' => 'Staf Purchase',
                'email' => 'purchase@bintang.com',
                'role' => 'purchase',
                'sales_code' => null,
                'credit_limit_quota' => 0,
            ],
            [
                'name' => 'Staf Finance',
                'email' => 'finance@bintang.com',
                'role' => 'finance',
                'sales_code' => null,
                'credit_limit_quota' => 0,
            ],
            [
                'name' => 'Mbak Kasir',
                'email' => 'kasir@bintang.com',
                'role' => 'kasir',
                'sales_code' => null,
                'credit_limit_quota' => 0,
            ],

            // 4. SALES (Butuh Limit Awal)
            [
                'name' => 'Andi Sales Lapangan',
                'email' => 'andi@bintang.com',
                'role' => 'sales_field',
                'sales_code' => 'SALES-001',
                'credit_limit_quota' => 5000000, // 5 JUTA (Cukup kecil biar cepat habis untuk tes warning)
            ],
            [
                'name' => 'Budi Sales toko',
                'email' => 'budi@bintang.com',
                'role' => 'sales_store',
                'sales_code' => 'SALES-002',
                'credit_limit_quota' => 5000000, // 5 JUTA
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']], // Cek email biar gak duplikat
                array_merge($userData, ['password' => $password])
            );
        }
    }
}
