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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();

            // 1. Objek apa yang mau diubah? (Contoh: 'App\Models\Product', 'App\Models\Customer')
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable(); // Nullable jika action-nya 'create' (belum punya ID)

            // 2. Mau diapakan?
            $table->enum('action', ['create', 'update', 'delete', 'credit_limit_update']);

            // 3. Data Perubahan (Disimpan dalam format JSON)
            // original_data: Data sebelum diedit (untuk history/rollback)
            // new_data: Data baru yang diajukan
            $table->json('original_data')->nullable();
            $table->json('new_data')->nullable();

            // 4. Status Persetujuan
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reason')->nullable(); // Alasan jika ditolak

            // 5. Siapa Pelakunya?
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade'); // Yang minta (Sales/Admin Gudang)
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null'); // Yang setuju (Manager)

            $table->timestamps();

            // Index biar pencarian cepat saat Manager buka menu Approval
            $table->index(['model_type', 'model_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
