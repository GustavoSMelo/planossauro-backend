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
        Schema::create('support_emails', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('ticketId');
            $table->string('category');
            $table->string('title');
            $table->longText('description');
            $table->timestamps();

            $table->foreignUuid('user_id')->references('uuid')->on('user')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_emails');
    }
};
