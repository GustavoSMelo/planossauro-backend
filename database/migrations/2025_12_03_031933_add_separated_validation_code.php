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
            $table->renameColumn('validation_code', 'github_validation_code');
            $table->renameColumn('is_validated', 'github_is_validated');
            $table->addColumn('integer', 'google_validation_code')->default(rand(10000, 99999));
            $table->addColumn('boolean', 'google_is_validated')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->renameColumn('github_validation_code', 'validation_code');
            $table->renameColumn('github_is_validated', 'is_validated');
            $table->dropColumn('google_validation_code');
            $table->dropColumn('google_is_validated');
        });
    }
};
