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
        if (Schema::hasTable('module_components')) {
            return;
        }

        Schema::create('module_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('sub_module_id')->constrained('sub_modules')->cascadeOnDelete();
            $table->string('code')->comment('Unique key within sub-module, e.g., leave_calendar, leave_balance');
            $table->string('name')->comment('Display name for the component');
            $table->text('description')->nullable();
            $table->string('type')->default('page')->comment('page, widget, api');
            $table->string('route')->nullable();
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module_id', 'sub_module_id', 'code']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_components');
    }
};
