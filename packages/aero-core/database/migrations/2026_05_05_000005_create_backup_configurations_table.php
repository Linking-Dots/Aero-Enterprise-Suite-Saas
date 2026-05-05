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
        Schema::create('backup_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default');
            $table->string('storage_driver')->default('local');
            $table->string('schedule_frequency')->default('daily');
            $table->integer('retention_days')->default(30);
            $table->boolean('encryption_enabled')->default(false);
            $table->string('notification_email')->nullable();
            $table->json('included_files')->nullable();
            $table->json('excluded_files')->nullable();
            $table->string('backup_type')->default('full');
            $table->boolean('active')->default(true);
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamps();
            
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_configurations');
    }
};
