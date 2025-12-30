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
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // Siapa yang input (Sales/Kasir)
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method'); // Transfer/Cash
            $table->string('proof_file')->nullable(); // Bukti Transfer
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending'); // <--- KUNCI APPROVAL
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_logs');
    }
};
