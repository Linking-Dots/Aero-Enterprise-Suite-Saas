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
            if (! Schema::hasColumn('system_settings', 'locale')) {
                $table->string('locale', 10)->nullable()->after('currency');
            }
            if (! Schema::hasColumn('system_settings', 'date_format')) {
                $table->string('date_format', 20)->nullable()->after('locale');
            }
            if (! Schema::hasColumn('system_settings', 'time_format')) {
                $table->string('time_format', 5)->nullable()->after('date_format');
            }
            if (! Schema::hasColumn('system_settings', 'first_day_of_week')) {
                $table->tinyInteger('first_day_of_week')->nullable()->after('time_format');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $columns = ['locale', 'date_format', 'time_format', 'first_day_of_week'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('system_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
