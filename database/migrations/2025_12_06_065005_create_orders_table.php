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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Sales yg input
            $table->foreignId('customer_id')->constrained()->onDelete('cascade'); // Toko yg beli

            $table->string('invoice_number')->unique(); // No Faktur (Misal: INV-001)
            $table->decimal('total_price', 12, 2)->default(0); // Total Belanja
            $table->enum('status', ['pending_approval', 'approved', 'completed', 'cancelled', 'rejected', 'shipped', 'delivered'])->default('pending_approval');
            $table->text('notes')->nullable(); // Catatan tambahan

            // Di dalam Schema::create('orders'...)
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid'); // Status Bayar
            $table->date('due_date')->nullable(); // Tanggal Jatuh Tempo
            $table->decimal('amount_paid', 12, 2)->default(0); // Yang sudah dibayar

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
