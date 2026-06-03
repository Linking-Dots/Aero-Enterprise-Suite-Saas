<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the modules table for the Module Permission Registry system.
     * Modules represent major functional areas (HRM, CRM, Project Management, etc.)
     */
    public function up(): void
    {
        // Guard against duplicate creation - aero-core creates these tables individually
        if (Schema::hasTable('modules')) {
            return;
        }

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Unique identifier: hrm, crm, project_mgmt');
            $table->string('name')->comment('Display name: Human Resources, CRM');
            $table->text('description')->nullable();
            $table->string('icon')->nullable()->comment('Icon class or path');
            $table->string('route_prefix')->nullable()->comment('Route prefix: /hrm, /crm');
            $table->string('category')->default('core_system')->comment('core_system, human_resources, etc.');
            $table->integer('priority')->default(0)->comment('Display order');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_core')->default(false)->comment('Core modules cannot be disabled');
            $table->json('settings')->nullable()->comment('Module-specific configuration');
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
            $table->index('category');
            $table->index(['is_active', 'priority']);
        });

        Schema::create('sub_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->string('code')->comment('Unique within module: employees, payroll');
            $table->string('name')->comment('Display name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('route')->nullable()->comment('Full route path');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['module_id', 'code']);
            $table->index(['module_id', 'is_active']);
        });

        Schema::create('module_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_module_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('code')->comment('Unique identifier: employee_list, payroll_generate');
            $table->string('name')->comment('Display name');
            $table->string('type')->default('page')->comment('page, action, widget, report');
            $table->string('route')->nullable()->comment('Route name or path');
            // The HRMAC module-hierarchy sync writes priority; central table omitted it.
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module_id', 'is_active']);
            $table->index(['sub_module_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_components');
        Schema::dropIfExists('sub_modules');
        Schema::dropIfExists('modules');
    }
};
