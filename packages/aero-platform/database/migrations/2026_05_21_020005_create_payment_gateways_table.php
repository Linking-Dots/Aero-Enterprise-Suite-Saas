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
        if (Schema::connection('central')->hasTable('payment_gateways')) {
            return;
        }

        Schema::connection('central')->create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->text('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('payment_gateways');
    }
};
