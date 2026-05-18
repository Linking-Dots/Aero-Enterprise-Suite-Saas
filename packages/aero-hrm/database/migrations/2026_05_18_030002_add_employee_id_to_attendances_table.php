<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('attendances', 'employee_id')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('employee_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();
        });

        // Backfill: set employee_id from employees table via user_id
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('
                UPDATE attendances a
                INNER JOIN employees e ON e.user_id = a.user_id
                SET a.employee_id = e.id
                WHERE a.employee_id IS NULL
            ');
        } else {
            DB::statement('
                UPDATE attendances
                SET employee_id = (
                    SELECT id FROM employees
                    WHERE employees.user_id = attendances.user_id
                    LIMIT 1
                )
                WHERE employee_id IS NULL
            ');
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('employee_id', 'attendances_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            try { $table->dropIndex('attendances_employee_id_idx'); } catch (\Exception $e) {}
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
