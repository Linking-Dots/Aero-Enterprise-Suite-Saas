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
        // Secrets / KMS
        if (! Schema::connection('central')->hasTable('kms_keys')) {
            Schema::connection('central')->create('kms_keys', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('provider')->default('local');
                $table->string('key_id')->unique();
                $table->string('status')->default('active');
                $table->timestamp('rotated_at')->nullable();
                $table->uuid('rotated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_deks')) {
            Schema::connection('central')->create('tenant_deks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->uuid('kms_key_id')->nullable()->index();
                $table->string('status')->default('active');
                $table->timestamp('rotated_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('vault_secrets')) {
            Schema::connection('central')->create('vault_secrets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->text('value');
                $table->string('scope')->default('platform');
                $table->string('tenant_id')->nullable()->index();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
            });
        }

        // Observability alerts
        if (! Schema::connection('central')->hasTable('observability_alerts')) {
            Schema::connection('central')->create('observability_alerts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('metric');
                $table->string('condition');
                $table->decimal('threshold', 12, 4);
                $table->string('severity')->default('warning');
                $table->boolean('is_active')->default(true);
                $table->json('channels')->nullable();
                $table->timestamps();
            });
        }

        // DR Runbooks
        if (! Schema::connection('central')->hasTable('dr_runbooks')) {
            Schema::connection('central')->create('dr_runbooks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('steps');
                $table->string('category')->default('general');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::connection('central')->hasTable('dr_runbook_executions')) {
            Schema::connection('central')->create('dr_runbook_executions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('runbook_id')->index();
                $table->string('status')->default('running');
                $table->json('step_results')->nullable();
                $table->uuid('executed_by')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('rto_rpo_configs')) {
            Schema::connection('central')->create('rto_rpo_configs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('service');
                $table->unsignedInteger('rto_minutes')->default(60);
                $table->unsignedInteger('rpo_minutes')->default(15);
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
                $table->unique('service');
            });
        }

        if (! Schema::connection('central')->hasTable('dr_drills')) {
            Schema::connection('central')->create('dr_drills', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('runbook_id')->nullable()->index();
                $table->string('status')->default('scheduled');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->text('outcome')->nullable();
                $table->uuid('scheduled_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('dr_drills');
        Schema::connection('central')->dropIfExists('rto_rpo_configs');
        Schema::connection('central')->dropIfExists('dr_runbook_executions');
        Schema::connection('central')->dropIfExists('dr_runbooks');
        Schema::connection('central')->dropIfExists('observability_alerts');
        Schema::connection('central')->dropIfExists('vault_secrets');
        Schema::connection('central')->dropIfExists('tenant_deks');
        Schema::connection('central')->dropIfExists('kms_keys');
    }
};
