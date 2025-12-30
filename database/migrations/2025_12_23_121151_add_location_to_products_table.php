<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration now adds foreign key columns for the new location structure.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add new foreign key columns
            $table->foreignId('gudang_id')->nullable()->after('stock')->constrained('gudangs')->onDelete('set null');
            $table->foreignId('gate_id')->nullable()->after('gudang_id')->constrained('gates')->onDelete('set null');
            $table->foreignId('block_id')->nullable()->after('gate_id')->constrained('blocks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop in reverse order of creation due to foreign key constraints
            $table->dropForeign(['block_id']);
            $table->dropForeign(['gate_id']);
            $table->dropForeign(['gudang_id']);
            $table->dropColumn(['block_id', 'gate_id', 'gudang_id']);
        });
    }
};
