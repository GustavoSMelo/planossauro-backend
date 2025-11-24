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
        Schema::table('user', function (Blueprint $table) {
            $table->addColumn('integer', 'sms_validation_code')->default(rand(10000, 99999));
            $table->addColumn('boolean', 'sms_is_validated')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('sms_validation_code');
            $table->dropColumn('sms_is_validated');
        });
    }
};
