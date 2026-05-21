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
        if (Schema::connection('central')->hasTable('partner_commissions')) {
            return;
        }

        Schema::connection('central')->create('partner_commissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained('reseller_partners')->cascadeOnDelete();
            $t->string('tenant_id');
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();

            $t->index('partner_id');
            $t->index('tenant_id');
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('partner_commissions');
    }
};
