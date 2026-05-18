<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'salary_structure_id')) {
                $table->foreignId('salary_structure_id')
                    ->nullable()
                    ->after('designation_id')
                    ->constrained('hrm_salary_structures')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['salary_structure_id']);
            $table->dropColumn('salary_structure_id');
        });
    }
};
