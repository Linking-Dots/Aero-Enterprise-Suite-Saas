<?php

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
        if (Schema::connection('central')->hasTable('feature_usage_events')) {
            return;
        }

        Schema::connection('central')->create('feature_usage_events', function (Blueprint $table) {
            $table->id();
            // tenants.id is a string (UUID) PK; tenant_id must match (foreignId = bigint
            // made `migrate` fail on MySQL with an incompatible-FK error).
            $table->string('tenant_id');
            $table->string('feature_code', 128);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['feature_code', 'occurred_at']);
            $table->index(['tenant_id', 'feature_code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('feature_usage_events');
    }
};
