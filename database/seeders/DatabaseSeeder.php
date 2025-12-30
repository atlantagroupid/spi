<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Ramsey\Collection\Set;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            // ProductSeeder::class,
            // CustomerSeeder::class,  // <--- Bikin 20 Toko
            // DemoDataSeeder::class,  // <--- Isi Transaksi ke 20 Toko tsb
            SettingSeeder::class,
        ]);
    }
}
