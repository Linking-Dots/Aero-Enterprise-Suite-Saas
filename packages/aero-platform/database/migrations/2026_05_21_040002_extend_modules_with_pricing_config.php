<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        Schema::connection('central')->table('modules', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('modules', 'config')) {
                $table->json('config')->nullable()->after('description');
            }
            if (! Schema::connection('central')->hasColumn('modules', 'price_monthly')) {
                $table->decimal('price_monthly', 10, 2)->default(0)->after('config');
            }
            if (! Schema::connection('central')->hasColumn('modules', 'price_annual')) {
                $table->decimal('price_annual', 10, 2)->default(0)->after('price_monthly');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('modules', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::connection('central')->hasColumn('modules', 'config') ? 'config' : null,
                Schema::connection('central')->hasColumn('modules', 'price_monthly') ? 'price_monthly' : null,
                Schema::connection('central')->hasColumn('modules', 'price_annual') ? 'price_annual' : null,
            ]));
        });
    }
};
