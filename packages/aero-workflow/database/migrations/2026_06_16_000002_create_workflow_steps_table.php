<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('name');
            $table->integer('order');
            $table->string('type'); // approval, notification, condition, automation
            $table->json('config'); // approvers, conditions, actions
            $table->boolean('is_parallel')->default(false);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('workflow_id');
            $table->index('order');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
