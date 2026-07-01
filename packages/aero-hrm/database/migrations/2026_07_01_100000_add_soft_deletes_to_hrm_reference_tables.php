<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the missing soft-delete column to HRM reference tables whose models use
 * SoftDeletes but whose create migrations never added `deleted_at`. Without it,
 * every query on these models fails with "Unknown column '<table>.deleted_at'".
 *
 * Idempotent + guarded so it is safe on already-correct schemas (standalone or
 * freshly-migrated tenants).
 */
return new class extends Migration
{
    /** @var string[] */
    private array $tables = ['grades', 'shift_schedules', 'salary_components'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropSoftDeletes();
                });
            }
        }
    }
};
