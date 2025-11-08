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
        Schema::create('user_planning', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->timestamps();
            $table->foreignUuid('user_id')->references('uuid')->on('user');
            $table->foreignUuid('planning_id')->references('uuid')->on('planning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_planning');
    }
};
