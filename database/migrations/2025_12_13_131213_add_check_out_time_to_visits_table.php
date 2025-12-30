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
    Schema::table('visits', function (Blueprint $table) {
        // Kolom check_out_time, nullable karena saat check-in belum ada isinya
        $table->timestamp('check_out_time')->nullable()->after('created_at');
    });
}

public function down()
{
    Schema::table('visits', function (Blueprint $table) {
        $table->dropColumn('check_out_time');
    });
}
};
