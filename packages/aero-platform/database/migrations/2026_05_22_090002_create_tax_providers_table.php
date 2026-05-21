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
        if (Schema::connection('central')->hasTable('tax_providers')) {
            return;
        }

        Schema::connection('central')->create('tax_providers', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name');
            // Encrypted JSON containing API keys and provider-specific secrets
            $t->text('config')->nullable();
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tax_providers');
    }
};
