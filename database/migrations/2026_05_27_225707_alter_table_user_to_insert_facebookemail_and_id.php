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
            $table->string('facebook_email')->nullable()->unique();
            $table->string('facebook_id')->nullable()->unique();
            $table->boolean('facebook_is_validated')->default(false);
            $table->integer('facebook_validation_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn(['facebook_email', 'facebook_id', 'facebook_is_validated', 'facebook_validation_code']);
        });
    }
};
