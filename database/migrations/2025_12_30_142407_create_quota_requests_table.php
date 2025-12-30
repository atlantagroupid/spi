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
    Schema::create('quota_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users'); // Siapa yang minta
        $table->double('amount'); // Berapa yang diminta
        $table->text('reason')->nullable(); // Alasan
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->foreignId('approver_id')->nullable()->constrained('users'); // Siapa yang setuju
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quota_requests');
    }
};
