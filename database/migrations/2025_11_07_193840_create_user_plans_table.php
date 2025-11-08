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
        Schema::create('user_plans', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->integer('amount_used');
            $table->date('date_verified');
            $table->date('date_renewal');
            $table->timestamps();

            $table->foreignUuid('user_id')->references('uuid')->on('user');
            $table->foreignUuid('plans_id')->references('uuid')->on('plans');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plans');
    }
};
