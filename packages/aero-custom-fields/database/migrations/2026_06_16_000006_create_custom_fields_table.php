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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('entity_type'); // e.g., 'users', 'employees', 'leaves'
            $table->enum('field_type', ['text', 'number', 'email', 'date', 'datetime', 'boolean', 'select', 'multi_select', 'textarea', 'file', 'currency']);
            $table->json('options')->nullable(); // For select/multi_select field options
            $table->json('validation_rules')->nullable(); // Validation rules (required, min, max, pattern, etc.)
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_searchable')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('placeholder')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entity_type', 'is_active']);
            $table->index(['entity_type', 'field_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
