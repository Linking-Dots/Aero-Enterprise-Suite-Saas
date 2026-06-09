<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the modules table in the landlord database.
     * This is needed because subsequent platform migrations alter the modules table,
     * but the table is normally only created programmatically in a later installation step.
     */
    public function up(): void
    {
        if (! Schema::hasTable('modules')) {
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
