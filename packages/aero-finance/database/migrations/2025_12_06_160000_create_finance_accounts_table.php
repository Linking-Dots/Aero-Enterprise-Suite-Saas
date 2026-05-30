<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the finance_accounts table for the Chart of Accounts (COA) system.
     * Supports hierarchical account structure with parent/child relationships.
     */
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Account code: 1000, 1100, etc.');
            $table->string('name')->comment('Account name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])->comment('Account type');
            $table->foreignId('parent_id')->nullable()->constrained('finance_accounts')->onDelete('restrict');
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('USD')->comment('ISO 4217 currency code');
            $table->boolean('is_active')->default(true);
            $table->decimal('balance', 15, 2)->default(0)->comment('Current account balance');
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('type');
            $table->index('is_active');
            $table->index('parent_id');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_accounts');
    }
};
