<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('visits', function (Blueprint $table) {
        // Taruh setelah customer_id biar rapi
        $table->timestamp('check_in_time')->nullable()->after('customer_id');
    });
}

public function down()
{
    Schema::table('visits', function (Blueprint $table) {
        $table->dropColumn('check_in_time');
    });
}
};
