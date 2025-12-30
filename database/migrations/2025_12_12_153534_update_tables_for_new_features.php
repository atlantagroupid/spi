<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Kategori Customer
        Schema::table('customers', function (Blueprint $table) {
            $table->string('category')
                ->default('Customer')->after('address');
        });

        // 2. Fitur Purchase (Diskon & Restock)
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('discount_price', 15, 2)->nullable()->after('price'); // Harga Coret
            $table->date('restock_date')->nullable()->after('stock'); // Tgl Pesan Barang
        });

        // 3. Fitur Kasir (Bukti Antar)
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_proof')->nullable()->after('payment_proof');
        });

        // 4. Target Omset Sales
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('monthly_sales_target', 15, 2)->default(0)->after('daily_visit_target');
        });
    }
};
