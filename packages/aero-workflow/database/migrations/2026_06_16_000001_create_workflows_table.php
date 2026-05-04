<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('entity_type'); // e.g., 'leave_request', 'expense_claim'
            $table->json('trigger_config'); // event-based, scheduled, manual
            $table->boolean('is_active')->default(true);
            $table->foreignId('template_id')->nullable()->constrained('workflow_templates')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('entity_type');
            $table->index('is_active');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
