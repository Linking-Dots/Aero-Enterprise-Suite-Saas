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
            // tenants.id is a string (UUID) PK; tenant_id must match (was foreignId =
            // bigint, which made `migrate` fail on MySQL with an incompatible-FK error).
            $table->string('tenant_id');
            $table->string('resource', 64); // storage_gb, api_calls, users, modules
            $table->bigInteger('limit_value');
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            // landlord_users.id is bigint (update_landlord_users_table_to_match_users_structure
            // migrated it from UUID → bigint), so set_by must be a bigint FK. The previous
            // foreignUuid produced char(36) and failed with an FK type/collation mismatch.
            $table->foreignId('set_by')->nullable()->constrained('landlord_users');
            $table->timestamps();
            $table->unique(['tenant_id', 'resource']);
            $table->index('expires_at');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_quota_overrides');
    }
};
