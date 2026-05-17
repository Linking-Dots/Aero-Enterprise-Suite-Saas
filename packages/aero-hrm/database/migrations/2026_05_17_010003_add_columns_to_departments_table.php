<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $t) {
            if (!Schema::hasColumn('departments', 'head_employee_id')) {
                $t->foreignId('head_employee_id')->nullable()->after('manager_id')
                  ->constrained('employees')->nullOnDelete();
            }
        });
    }
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $t) {
            if (Schema::hasColumn('departments', 'head_employee_id')) {
                $t->dropConstrainedForeignId('head_employee_id');
            }
        });
    }
};
