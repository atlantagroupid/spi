<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    // 1. Tabel Kategori
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique(); // Nama Kategori (Contoh: Lantai)
        $table->timestamps();
    });

    // 2. Tabel Pengaturan (Key-Value Store)
    Schema::create('settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique(); // Kunci (Contoh: app_name)
        $table->text('value')->nullable(); // Isi (Contoh: Bintang Interior)
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('categories');
    Schema::dropIfExists('settings');
}
};
