<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // Hapus kolom sales_code (sesuaikan nama kolom di database Anda, misal: sales_code atau code)
        if (Schema::hasColumn('users', 'sales_code')) {
            $table->dropColumn('sales_code');
        }
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        // Buat jaga-jaga kalau mau rollback
        $table->string('sales_code')->nullable();
    });
}
};
