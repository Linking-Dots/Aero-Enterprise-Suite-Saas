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
        if (Schema::connection('central')->hasTable('regional_prices')) {
            return;
        }

        Schema::connection('central')->create('regional_prices', function (Blueprint $t) {
            $t->id();
            // Polymorphic: priceable_type = Plan|Product, priceable_id = their PK
            $t->morphs('priceable');
            $t->string('currency_code', 3);
            $t->decimal('price_monthly', 12, 2);
            $t->decimal('price_annual', 12, 2);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(
                ['priceable_type', 'priceable_id', 'currency_code'],
                'regional_prices_priceable_currency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('regional_prices');
    }
};
