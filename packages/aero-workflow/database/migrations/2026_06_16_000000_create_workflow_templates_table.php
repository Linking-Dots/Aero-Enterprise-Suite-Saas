<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('entity_type');
            $table->json('steps_config'); // reusable step definitions
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // system templates cannot be deleted
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('entity_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_templates');
    }
};
