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
        Schema::create('subscription', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->integer('daily_plans_used');
            $table->integer('weekly_plans_used');
            $table->date('date_verified')->nullable();
            $table->date('next_billing')->nullable();
            $table->string('status');
            $table->integer('last_four_digits')->nullable();
            $table->timestamps();

            $table->foreignUuid('user_id')->unique()->references('uuid')->on('user');
            $table->foreignUuid('plans_id')->unique()->references('uuid')->on('plans');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription');
    }
};
