<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('employees', 'national_id')) {
                $table->text('national_id')->nullable()->after('emirates_id');
            }
            if (!Schema::hasColumn('employees', 'bank_account_number')) {
                $table->text('bank_account_number')->nullable()->after('basic_salary');
            }
            // Widen existing PII columns to TEXT for encrypted payloads
            foreach (['passport_no', 'visa_no', 'emirates_id'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->text($col)->nullable()->change();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('employees', 'national_id')) {
                $table->dropColumn('national_id');
            }
            if (Schema::hasColumn('employees', 'bank_account_number')) {
                $table->dropColumn('bank_account_number');
            }
        });
    }
};
