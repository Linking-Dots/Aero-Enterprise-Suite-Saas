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
        Schema::create('installation_progress', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('step')->nullable();
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('percentage')->default(0);
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('status');
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
