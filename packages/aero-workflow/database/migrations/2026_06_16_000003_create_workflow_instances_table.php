<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('status'); // pending, approved, rejected, escalated, completed
            $table->json('context'); // runtime data
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            // Indexes
            $table->index('workflow_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
            $table->index('current_step_id');
            $table->index('initiated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
