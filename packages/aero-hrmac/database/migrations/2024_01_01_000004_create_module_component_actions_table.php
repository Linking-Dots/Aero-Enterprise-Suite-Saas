<?php

declare(strict_types=1);

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
        if (Schema::hasTable('module_component_actions')) {
            return;
        }

        Schema::create('module_component_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_component_id')->constrained('module_components')->cascadeOnDelete();
            $table->string('code')->comment('Unique action key within component, e.g., create, edit, delete, approve');
            $table->string('name')->comment('Display name for the action');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module_component_id', 'code']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_component_actions');
    }
};
