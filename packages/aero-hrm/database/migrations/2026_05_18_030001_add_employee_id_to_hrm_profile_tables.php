<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive migration: add employee_id FK to all legacy HRM profile tables.
 *
 * Strategy: column added nullable alongside existing user_id, backfilled
 * via JOIN on employees, then indexed. user_id is NOT dropped — existing
 * H-1 controllers still reference it; they will be migrated in a separate pass.
 *
 * Tables affected:
 *  - employee_personal_documents
 *  - emergency_contacts
 *  - employee_addresses
 *  - employee_education
 *  - employee_work_experience
 *  - employee_bank_details  (unique constraint — one record per employee)
 *  - employee_dependents
 *  - employee_certifications
 */
return new class extends Migration
{
    /** Tables that receive a plain (non-unique) employee_id FK. */
    private array $tables = [
        'employee_personal_documents',
        'emergency_contacts',
        'employee_addresses',
        'employee_education',
        'employee_work_experience',
        'employee_dependents',
        'employee_certifications',
    ];

    public function up(): void
    {
        // ── 1. Add employee_id (nullable FK) to each non-unique table ─────────
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'employee_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('employee_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('employees')
                    ->cascadeOnDelete();
            });
        }

        // ── 2. employee_bank_details: unique employee_id (1:1 per employee) ───
        if (! Schema::hasColumn('employee_bank_details', 'employee_id')) {
            Schema::table('employee_bank_details', function (Blueprint $t) {
                $t->foreignId('employee_id')
                    ->nullable()
                    ->unique()
                    ->after('user_id')
                    ->constrained('employees')
                    ->cascadeOnDelete();
            });
        }

        // ── 3. Backfill: set employee_id from employees.id where user_id matches
        $allTables = array_merge($this->tables, ['employee_bank_details']);

        foreach ($allTables as $table) {
            if (DB::getDriverName() !== 'sqlite') {
                // MySQL / PostgreSQL — supports JOIN in UPDATE
                DB::statement("
                    UPDATE {$table} t
                    INNER JOIN employees e ON e.user_id = t.user_id
                    SET t.employee_id = e.id
                    WHERE t.employee_id IS NULL
                ");
            } else {
                // SQLite — correlated sub-select
                DB::statement("
                    UPDATE {$table}
                    SET employee_id = (
                        SELECT id
                        FROM employees
                        WHERE employees.user_id = {$table}.user_id
                        LIMIT 1
                    )
                    WHERE employee_id IS NULL
                ");
            }
        }

        // ── 4. Add index on employee_id for the non-unique tables ─────────────
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $indexName = "{$table}_employee_id_idx";

                try {
                    $t->index('employee_id', $indexName);
                } catch (\Exception $e) {
                    // Index already exists — skip silently
                }
            });
        }
    }

    public function down(): void
    {
        $allTables = array_merge($this->tables, ['employee_bank_details']);

        foreach ($allTables as $table) {
            if (! Schema::hasColumn($table, 'employee_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                // Drop plain index only on non-unique tables (bank_details uses unique idx)
                if ($table !== 'employee_bank_details') {
                    try {
                        $t->dropIndex("{$table}_employee_id_idx");
                    } catch (\Exception $e) {
                        // Index may not exist — skip
                    }
                }

                $t->dropForeign(['employee_id']);
                $t->dropColumn('employee_id');
            });
        }
    }
};
