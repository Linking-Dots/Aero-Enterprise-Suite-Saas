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
        if (Schema::hasTable('installation_progress')) {
            return;
        }

        Schema::create('installation_progress', function (Blueprint $table) {
            $table->string('session_id')->primary();
            $table->string('step');
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_progress');
    }
};
