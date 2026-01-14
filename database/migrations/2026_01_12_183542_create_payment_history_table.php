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
        Schema::create('payment_history', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->date('payment_date');
            $table->string('description');
            $table->string('card_brand');
            $table->integer('last_four_digits');
            $table->float('price');
            $table->string('status');
            $table->string('NFe')->nullable()->default(null);
            $table->timestamps();

            $table->foreignUuid('plan_id')->references('uuid')->on('plans');
            $table->foreignUuid('user_id')->references('uuid')->on('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_history');
    }
};
