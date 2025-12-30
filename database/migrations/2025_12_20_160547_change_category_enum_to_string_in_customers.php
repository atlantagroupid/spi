<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('customers', function (Blueprint $table) {
        // Ubah dari ENUM ke STRING agar fleksibel
        // Pastikan install doctrine/dbal jika Laravel versi lama (composer require doctrine/dbal)
        $table->string('category', 100)->change();
    });
}

public function down()
{
    Schema::table('customers', function (Blueprint $table) {
        // Kembalikan ke ENUM jika rollback (sesuaikan isinya dengan enum lama Anda)
        $table->enum('category', ['Workshop', 'Studio', 'Kontraktor', 'Customer'])->change();
    });
}
};
