<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_submissions', function (Blueprint $table) {
            $table->id();

            // Siapa sales yang mengajukan
            $table->foreignId('sales_id')->constrained('users')->onDelete('cascade');

            // Customer mana yang diajukan
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');

            // Nominal & Hari yang diminta
            $table->decimal('submission_limit', 15, 2);
            $table->integer('submission_days');

            // Status pengajuan
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Siapa manager yang menyetujui (bisa null kalau belum diapprove)
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

            // Catatan tambahan jika ada revisi/alasan tolak
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_submissions');
    }
};
