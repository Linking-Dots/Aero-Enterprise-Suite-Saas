<?php

declare(strict_types=1);

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
        Schema::connection('central')->table('plans', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('plans', 'price_monthly')) {
                $table->decimal('price_monthly', 12, 2)->default(0)->after('monthly_price');
            }
            if (! Schema::connection('central')->hasColumn('plans', 'price_annual')) {
                $table->decimal('price_annual', 12, 2)->default(0)->after('price_monthly');
            }
            if (! Schema::connection('central')->hasColumn('plans', 'status')) {
                $table->string('status', 16)->default('active')->after('is_active');
            }
            if (! Schema::connection('central')->hasColumn('plans', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('status');
            }
            if (! Schema::connection('central')->hasColumn('plans', 'stripe_price_id_monthly')) {
                $table->string('stripe_price_id_monthly')->nullable()->after('stripe_product_id');
            }
            if (! Schema::connection('central')->hasColumn('plans', 'stripe_price_id_annual')) {
                $table->string('stripe_price_id_annual')->nullable()->after('stripe_price_id_monthly');
            }
        });

        // Add indexes if they don't exist — use Laravel 12 native schema introspection.
        // (Doctrine DBAL / getDoctrineSchemaManager was removed in Laravel 11.)
        $existingIndexes = Schema::connection('central')->getIndexes('plans');
        $existingIndexNames = array_column($existingIndexes, 'name');

        if (! in_array('plans_status_index', $existingIndexNames, true)) {
            Schema::connection('central')->table('plans', function (Blueprint $table) {
                $table->index('status');
            });
        }
        if (! in_array('plans_is_public_index', $existingIndexNames, true)) {
            Schema::connection('central')->table('plans', function (Blueprint $table) {
                $table->index('is_public');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->table('plans', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::connection('central')->hasColumn('plans', 'price_monthly') ? 'price_monthly' : null,
                Schema::connection('central')->hasColumn('plans', 'price_annual') ? 'price_annual' : null,
                Schema::connection('central')->hasColumn('plans', 'status') ? 'status' : null,
                Schema::connection('central')->hasColumn('plans', 'is_public') ? 'is_public' : null,
                Schema::connection('central')->hasColumn('plans', 'stripe_price_id_monthly') ? 'stripe_price_id_monthly' : null,
                Schema::connection('central')->hasColumn('plans', 'stripe_price_id_annual') ? 'stripe_price_id_annual' : null,
            ]));
        });
    }
};
