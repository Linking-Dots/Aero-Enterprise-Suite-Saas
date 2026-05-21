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
        if (Schema::connection('central')->hasTable('tax_rates')) {
            return;
        }

        Schema::connection('central')->create('tax_rates', function (Blueprint $t) {
            $t->id();
            $t->string('country_code', 2);
            $t->string('region_code', 10)->nullable();
            $t->string('name');
            $t->decimal('rate', 6, 4);
            $t->enum('type', ['vat', 'gst', 'sales_tax', 'withholding'])->default('vat');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['country_code', 'region_code', 'type']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tax_rates');
    }
};
