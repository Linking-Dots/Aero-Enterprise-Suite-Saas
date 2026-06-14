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
        Schema::create('system_health_logs', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type', 50); // e.g., 'system_overview', 'database', 'queue', 'cache'
            $table->string('metric_name', 100); // e.g., 'cpu_usage', 'memory_usage', 'connection_count'
            $table->float('value');
            $table->string('unit', 20)->nullable(); // e.g., 'percent', 'bytes', 'count'
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamp('timestamp');
            $table->string('tenant_id')->nullable(); // For multi-tenancy
            $table->index(['metric_type', 'metric_name', 'timestamp']);
            $table->index('timestamp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_health_logs');
    }
};
