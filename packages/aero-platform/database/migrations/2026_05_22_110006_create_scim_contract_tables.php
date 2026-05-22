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
        // SCIM Endpoints
        if (! Schema::connection('central')->hasTable('scim_endpoints')) {
            Schema::connection('central')->create('scim_endpoints', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->unique()->index();
                $table->text('token');
                $table->string('provider')->default('okta');
                $table->boolean('is_active')->default(true);
                $table->timestamp('token_rotated_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('scim_sync_logs')) {
            Schema::connection('central')->create('scim_sync_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->string('operation');
                $table->string('resource_type');
                $table->string('resource_id')->nullable();
                $table->string('status');
                $table->json('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        // MSA / Order Forms
        if (! Schema::connection('central')->hasTable('master_service_agreements')) {
            Schema::connection('central')->create('master_service_agreements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->string('title');
                $table->longText('content_md')->nullable();
                $table->string('status')->default('draft');
                $table->string('signed_by_name')->nullable();
                $table->string('signed_by_email')->nullable();
                $table->string('signed_by_ip')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::connection('central')->hasTable('order_forms')) {
            Schema::connection('central')->create('order_forms', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->uuid('plan_id')->nullable()->index();
                $table->json('product_ids')->nullable();
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->string('status')->default('draft');
                $table->uuid('msa_id')->nullable()->index();
                $table->string('signed_by_name')->nullable();
                $table->string('signed_by_email')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::connection('central')->hasTable('rate_cards')) {
            Schema::connection('central')->create('rate_cards', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('tenant_id')->nullable()->index();
                $table->json('line_items')->nullable();
                $table->boolean('is_active')->default(true);
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('rate_cards');
        Schema::connection('central')->dropIfExists('order_forms');
        Schema::connection('central')->dropIfExists('master_service_agreements');
        Schema::connection('central')->dropIfExists('scim_sync_logs');
        Schema::connection('central')->dropIfExists('scim_endpoints');
    }
};
