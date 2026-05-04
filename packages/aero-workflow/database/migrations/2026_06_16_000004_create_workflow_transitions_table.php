<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignId('from_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->foreignId('to_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('action'); // approve, reject, escalate, skip
            $table->text('comment')->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('instance_id');
            $table->index('performed_by');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
