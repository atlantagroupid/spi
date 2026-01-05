<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <--- Jangan lupa import ini di paling atas!

return new class extends Migration
{

public function up()
{
    // Kita ubah kolom status agar menerima 'in_progress'
    // Cek driver database untuk kompatibilitas
    if (DB::getDriverName() === 'sqlite') {
        // SQLite doesn't support MODIFY COLUMN, use different approach
        // For testing purposes, we'll recreate the table or just skip
        // Since this is a test environment, we can safely skip enum modifications
        return;
    }

    // MySQL/MariaDB: Use raw SQL for ENUM modification
    DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('planned', 'in_progress', 'completed') NOT NULL DEFAULT 'planned'");
}

public function down()
{
    // Kembalikan ke semula jika rollback (opsional)
    if (DB::getDriverName() === 'sqlite') {
        // Skip for SQLite testing
        return;
    }

    DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('planned', 'completed') NOT NULL DEFAULT 'planned'");
}
};
