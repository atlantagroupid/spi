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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Siapa salesnya
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete(); // Toko mana

            // Rencana & Realisasi
            // For SQLite compatibility, use string with default instead of enum
            $table->string('status')->default('planned'); // Status kunjungan: planned, in_progress, completed, cancelled
            $table->date('visit_date'); // Tanggal rencana kunjungan

            // Data Check-in (GPS & Foto)
            $table->text('notes')->nullable(); // Laporan hasil kunjungan
            $table->string('photo_path')->nullable(); // Bukti Foto
            $table->string('latitude')->nullable(); // Titik GPS
            $table->string('longitude')->nullable(); // Titik GPS

            $table->timestamp('completed_at')->nullable(); // Jam berapa check-in dilakukan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
