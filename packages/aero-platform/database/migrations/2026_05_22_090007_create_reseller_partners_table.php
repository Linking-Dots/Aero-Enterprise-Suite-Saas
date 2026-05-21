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
        if (Schema::connection('central')->hasTable('reseller_partners')) {
            return;
        }

        Schema::connection('central')->create('reseller_partners', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->decimal('commission_rate', 5, 4)->default(0.1000);
            $t->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $t->string('portal_slug')->unique()->nullable();
            $t->json('portal_config')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('reseller_partners');
    }
};
