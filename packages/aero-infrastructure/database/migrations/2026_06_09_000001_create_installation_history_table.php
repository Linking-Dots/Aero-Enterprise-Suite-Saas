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
        if (Schema::hasTable('installation_history')) {
            return;
        }

        Schema::create('installation_history', function (Blueprint $table) {
            $table->id();
            $table->string('step_name')->index()->comment('Step identifier: e.g., config, database, migration, seeding, admin, modules, settings, cache, license, finalize');
            $table->integer('step_order')->comment('Step sequence number (1-9)');
            $table->enum('mode', ['saas', 'standalone'])->comment('Installation mode');
            $table->string('migration_name')->nullable()->comment('Migration file name if applicable');
            $table->string('status')->default('pending')->comment('pending, in_progress, success, failed, rolled_back');
            $table->text('error_message')->nullable()->comment('Error details if step failed');
            $table->integer('duration_ms')->nullable()->comment('Execution time in milliseconds');
            $table->integer('batch')->nullable()->comment('Migration batch number (for grouping)');
            $table->integer('rows_affected')->nullable()->comment('Rows inserted/updated if applicable');
            $table->json('metadata')->nullable()->comment('Additional context: tables created, seeders run, etc.');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['mode', 'step_order', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_history');
    }
};
