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
            $table->string('user_email')->nullable()->unique();
            $table->string('user_password')->nullable();
            $table->boolean('email_is_validated')->default(false);
            $table->integer('email_validation_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn(['user_email', 'user_password', 'email_is_validated', 'email_validation_code']);
        });
    }
};
