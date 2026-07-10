<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spatie activity_log table for the central database.
 *
 * 25+ central platform models (ResellerPartner, PartnerCommission, Coupon,
 * Refund, CreditNote, TenantBranding, …) use the LogsActivity trait, which
 * writes an Activity row on every save. The table was never created on the
 * central connection, so any write to one of those models 500ed. Mirrors
 * Spatie's three migrations (base + event column + batch_uuid) in one table.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        if (Schema::connection('central')->hasTable('activity_log')) {
            return;
        }

        Schema::connection('central')->create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('activity_log');
    }
};
