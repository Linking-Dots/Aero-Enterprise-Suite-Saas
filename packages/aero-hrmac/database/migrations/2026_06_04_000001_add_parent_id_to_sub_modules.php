<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nested sub-modules: a sub_module may have a parent sub_module (self-referencing),
 * forming a tree under one module. Flat data (parent_id = null) is unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sub_modules') || Schema::hasColumn('sub_modules', 'parent_id')) {
            return;
        }

        Schema::table('sub_modules', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('module_id');
            $table->foreign('parent_id')->references('id')->on('sub_modules')->nullOnDelete();
            $table->index(['module_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('sub_modules') && Schema::hasColumn('sub_modules', 'parent_id')) {
            Schema::table('sub_modules', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};
