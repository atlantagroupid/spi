<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Kita tambahkan kolom payment_type (cash/top/kredit)
            // Default kita set 'cash' agar data lama tidak error
            $table->enum('payment_type', ['cash', 'top', 'kredit', 'transfer'])
                  ->default('cash')
                  ->after('total_price');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
