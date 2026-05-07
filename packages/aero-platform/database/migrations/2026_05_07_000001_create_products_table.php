<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('module_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_marketplace_visible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('version')->default('1.0.0');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('module_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
