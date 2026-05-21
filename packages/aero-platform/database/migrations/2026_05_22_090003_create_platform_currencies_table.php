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
        if (Schema::connection('central')->hasTable('platform_currencies')) {
            return;
        }

        Schema::connection('central')->create('platform_currencies', function (Blueprint $t) {
            $t->id();
            $t->string('code', 3)->unique();
            $t->string('name');
            $t->string('symbol', 8);
            $t->decimal('exchange_rate_to_usd', 16, 8)->default(1);
            $t->boolean('is_active')->default(true);
            $t->timestamp('rate_updated_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_currencies');
    }
};
