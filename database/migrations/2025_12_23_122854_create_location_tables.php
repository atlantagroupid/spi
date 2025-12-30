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
        Schema::create('gudangs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->unique(['gudang_id', 'name']);
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_id')->constrained('gates')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->unique(['gate_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('gates');
        Schema::dropIfExists('gudangs');
    }
};