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
        Schema::create('saved_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('module_code', 50)->index(); // which module this view belongs to (e.g., 'hrm', 'crm', 'finance')
            $table->string('route')->index(); // the route where this view applies
            $table->string('name'); // user-defined name for the view
            $table->text('description')->nullable(); // optional description
            $table->json('filters'); // filter configuration (column filters, date ranges, search terms, etc.)
            $table->json('sort')->nullable(); // sort configuration (column, direction)
            $table->json('columns')->nullable(); // visible/hide columns configuration
            $table->boolean('is_default')->default(false); // whether this is the user's default view for this route
            $table->boolean('is_shared')->default(false); // whether this view is shared with other users
            $table->json('shared_with')->nullable(); // array of user IDs or role IDs this view is shared with
            $table->boolean('is_system')->default(false); // system-wide view created by admin (not user-specific)
            $table->timestamps();
            $table->softDeletes(); // SavedView model uses SoftDeletes

            $table->index(['user_id', 'module_code']);
            $table->index(['module_code', 'route']);
            $table->index('is_default');
            $table->index('is_shared');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_views');
    }
};
