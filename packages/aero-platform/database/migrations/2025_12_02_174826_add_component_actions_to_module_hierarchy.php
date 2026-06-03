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
        // Add description field to module_components (guard in case core already added it)
        if (Schema::hasTable('module_components') && !Schema::hasColumn('module_components', 'description')) {
            Schema::table('module_components', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }

        // Create module_component_actions table (guard in case core already created it)
        if (Schema::hasTable('module_component_actions')) {
            return;
        }

        Schema::create('module_component_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_component_id')->constrained()->onDelete('cascade');
            $table->string('code')->comment('Action code: view, create, update, delete, etc.');
            $table->string('name')->comment('Display name for the action');
            $table->text('description')->nullable();
            // The HRMAC module-hierarchy sync writes is_active; central table omitted it.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module_component_id', 'code']);
            $table->index('module_component_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('module_component_actions');

        Schema::table('module_components', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
