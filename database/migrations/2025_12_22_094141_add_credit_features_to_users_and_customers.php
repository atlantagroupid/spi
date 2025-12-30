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
        // 1. Tambah Quota ke User (Sales/Manager)
        Schema::table('users', function (Blueprint $table) {
            // Ini adalah "Plafon" atau jatah maksimal user boleh memberikan utang
            // Contoh: Sales A = 5 Juta, Manager = 500 Juta
            $table->decimal('credit_limit_quota', 15, 2)->default(0)->after('email');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('credit_limit_quota');
        });
    }
};
