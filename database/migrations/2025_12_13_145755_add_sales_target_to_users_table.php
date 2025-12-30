<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom untuk target omset (misal: Rp 50.000.000)
            // Pakai bigInteger atau decimal agar muat angka besar
            $table->bigInteger('sales_target')->default(0)->after('daily_visit_target');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sales_target');
        });
    }
};
