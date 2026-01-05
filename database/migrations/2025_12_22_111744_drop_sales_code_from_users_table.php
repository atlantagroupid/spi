<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus index unik dulu agar SQLite tidak error
            if (Schema::hasColumn('users', 'sales_code')) {
                // Drop unique index first (required for SQLite compatibility)
                try {
                    $table->dropUnique(['sales_code']);
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }

                // Then drop the column
                $table->dropColumn('sales_code');
            }
        });
    }

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        // Buat jaga-jaga kalau mau rollback
        $table->string('sales_code')->nullable();
    });
}
};
