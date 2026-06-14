<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_progress', function (Blueprint $table) {
            $table->string('session_id')->primary();
            $table->string('step');
            $table->string('status');
            $table->integer('percentage')->default(0);
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_progress');
    }
};
