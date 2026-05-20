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
        if (Schema::connection('central')->hasTable('tenant_quota_overrides')) {
            return;
        }

        Schema::connection('central')->create('tenant_quota_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('resource', 64); // storage_gb, api_calls, users, modules
            $table->bigInteger('limit_value');
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('set_by')->constrained('landlord_users');
            $table->timestamps();
            $table->unique(['tenant_id', 'resource']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_quota_overrides');
    }
};
