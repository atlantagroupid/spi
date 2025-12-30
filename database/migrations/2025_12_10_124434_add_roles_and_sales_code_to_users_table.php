<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. SOLUSI ERROR 'Duplicate column': Cek dulu apakah kolomnya sudah ada?
            if (!Schema::hasColumn('users', 'sales_code')) {
                // Kalau belum ada, baru dibuat
                $table->string('sales_code')->nullable()->unique()->after('email');
            }

            // 2. SOLUSI ERROR 'Data Truncated': Ubah kolom role jadi STRING
            // Ini akan menghapus batasan ENUM lama, jadi bisa diisi 'manager_operasional', 'kasir', dll.
            if (Schema::hasColumn('users', 'role')) {
                $table->string('role')->change();
            } else {
                // Jaga-jaga kalau kolom role belum ada (tergantung migration sebelumnya)
                $table->string('role')->default('sales');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus sales_code kalau rollback
            if (Schema::hasColumn('users', 'sales_code')) {
                $table->dropColumn('sales_code');
            }
            // Kita tidak perlu mengembalikan role ke enum sempit agar data aman
        });
    }
};
