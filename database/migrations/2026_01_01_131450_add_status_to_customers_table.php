<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('customers', function (Blueprint $table) {
        // Default 'active' agar data lama tidak error
        $table->enum('status', ['active', 'pending_approval', 'rejected'])->default('active')->after('credit_limit');
    });
}

public function down()
{
    Schema::table('customers', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}
};
