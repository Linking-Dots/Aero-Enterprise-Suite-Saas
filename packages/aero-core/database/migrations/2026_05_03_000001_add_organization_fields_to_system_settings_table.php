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
        Schema::table('system_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('system_settings', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('country');
            }
            if (! Schema::hasColumn('system_settings', 'vat_number')) {
                $table->string('vat_number')->nullable()->after('tax_id');
            }
            if (! Schema::hasColumn('system_settings', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('vat_number');
            }
            if (! Schema::hasColumn('system_settings', 'industry')) {
                $table->string('industry')->nullable()->after('registration_number');
            }
            if (! Schema::hasColumn('system_settings', 'mobile_number')) {
                $table->string('mobile_number')->nullable()->after('support_phone');
            }
            if (! Schema::hasColumn('system_settings', 'fax')) {
                $table->string('fax')->nullable()->after('mobile_number');
            }
            if (! Schema::hasColumn('system_settings', 'fiscal_year_start')) {
                $table->date('fiscal_year_start')->nullable()->after('industry');
            }
            if (! Schema::hasColumn('system_settings', 'fiscal_year_end')) {
                $table->date('fiscal_year_end')->nullable()->after('fiscal_year_start');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $columns = ['tax_id', 'vat_number', 'registration_number', 'industry', 'mobile_number', 'fax', 'fiscal_year_start', 'fiscal_year_end'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('system_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
