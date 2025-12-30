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
    // Ini perintah SQL murni (Raw SQL) agar lebih aman untuk ENUM
    DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('planned', 'in_progress', 'completed') NOT NULL DEFAULT 'planned'");
}

public function down()
{
    // Kembalikan ke semula jika rollback (opsional)
    DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('planned', 'completed') NOT NULL DEFAULT 'planned'");
}
};
