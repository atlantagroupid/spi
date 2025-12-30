<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom Role di User
        Schema::table('users', function (Blueprint $table) {
            // Kita pakai string biasa atau enum. Default 'sales_field' (Sales Lapangan)
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('sales_field')->after('email');
                // Values: 'sales_field', 'sales_store', 'manager_ops', 'manager_biz'
            }
        });

        // 2. Buat Latitude/Longitude jadi Boleh Kosong (Nullable)
        Schema::table('visits', function (Blueprint $table) {
            // Asumsi nama tabel kunjunganmu 'visits'
            $table->string('latitude')->nullable()->change();
            $table->string('longitude')->nullable()->change();
            // Tambah tipe kunjungan (opsional, untuk filter laporan)
            $table->string('visit_type')->default('field')->after('customer_id'); // 'field' or 'store'
        });
    }

    public function down(): void
    {
        // Rollback logic (optional)
    }
};
