<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P-7 — Feature Flags, Experiments, Tenant Communications & enhanced API/Webhook tables.
 *
 * All tables reside on the central (landlord) database.
 * Schema::connection('central') is used for clarity; hasTable/hasColumn guards
 * prevent duplicate-column failures on re-run.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        // -------------------------------------------------------------------------
        // Augment integrations_api_keys with P-7 columns
        // -------------------------------------------------------------------------
        if (Schema::connection('central')->hasTable('integrations_api_keys')) {
            Schema::connection('central')->table('integrations_api_keys', function (Blueprint $table) {
                if (! Schema::connection('central')->hasColumn('integrations_api_keys', 'key_prefix')) {
                    $table->string('key_prefix', 8)->nullable()->after('name')
                        ->comment('First 8 chars of raw key for display lookup');
                }
                if (! Schema::connection('central')->hasColumn('integrations_api_keys', 'key_hash')) {
                    $table->string('key_hash', 255)->nullable()->after('key_prefix')
                        ->comment('bcrypt hash of raw key');
                }
                if (! Schema::connection('central')->hasColumn('integrations_api_keys', 'revoked_at')) {
                    $table->timestamp('revoked_at')->nullable()->after('expires_at');
                }
                if (! Schema::connection('central')->hasColumn('integrations_api_keys', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('revoked_at')
                        ->comment('FK landlord_users');
                }
            });
        }

        // -------------------------------------------------------------------------
        // Platform Webhook Endpoints (P-7 canonical table)
        // -------------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('platform_webhook_endpoints')) {
            Schema::connection('central')->create('platform_webhook_endpoints', function (Blueprint $table) {
                $table->id();
                $table->string('url', 500);
                $table->text('description')->nullable();
                $table->json('events')->comment('Array of event type strings');
                $table->text('secret')->nullable()->comment('Encrypted signing secret');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('failure_count')->default(0);
                $table->timestamp('last_triggered_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('is_active');
            });
        }

        // -------------------------------------------------------------------------
        // Platform Webhook Delivery Logs
        // -------------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('platform_webhook_delivery_logs')) {
            Schema::connection('central')->create('platform_webhook_delivery_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('endpoint_id');
                $table->string('event_type', 100);
                $table->json('payload')->nullable();
                $table->unsignedSmallInteger('response_status')->nullable();
                $table->text('response_body')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('next_retry_at')->nullable();
                $table->timestamps();

                $table->index('endpoint_id');
                $table->index('event_type');
                $table->index('delivered_at');
            });
        }

        // -------------------------------------------------------------------------
        // Feature Flags
        // -------------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('feature_flags')) {
            Schema::connection('central')->create('feature_flags', function (Blueprint $table) {
                $table->id();
                $table->string('code', 100)->unique();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(false);
                $table->unsignedTinyInteger('rollout_pct')->default(0)
                    ->comment('0–100 global rollout percentage');
                $table->boolean('is_archived')->default(false);
                $table->unsignedBigInteger('created_by')->nullable()
                    ->comment('FK landlord_users');
                $table->timestamps();

                $table->index('is_archived');
                $table->index('is_active');
            });
        }

        // -------------------------------------------------------------------------
        // Feature Flag Tenant Overrides
        // -------------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('feature_flag_tenant_overrides')) {
            Schema::connection('central')->create('feature_flag_tenant_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('flag_id');
                $table->string('tenant_id', 36)->comment('Tenancy tenant identifier');
                $table->boolean('is_active');
                $table->unsignedBigInteger('set_by')->nullable()
                    ->comment('FK landlord_users');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['flag_id', 'tenant_id']);
                $table->index('flag_id');
                $table->index('tenant_id');
            });
        }

        // -------------------------------------------------------------------------
        // Experiments (A/B)
        // -------------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('feature_flag_experiments')) {
            Schema::connection('central')->create('feature_flag_experiments', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->unsignedBigInteger('flag_id');
                $table->unsignedTinyInteger('control_pct')->default(50);
                $table->unsignedTinyInteger('variant_pct')->default(50);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('stopped_at')->nullable();
                $table->string('winner', 20)->nullable()
                    ->comment('control | variant | null');
                $table->timestamps();

                $table->index('flag_id');
            });
        }

        // -------------------------------------------------------------------------
        // Tenant Broadcasts
        // -------------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('tenant_broadcasts')) {
            Schema::connection('central')->create('tenant_broadcasts', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('body')->comment('HTML body');
                $table->string('target', 20)->default('all')
                    ->comment('all | specific');
                $table->json('target_tenant_ids')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->unsignedInteger('dismissed_count')->default(0);
                $table->unsignedBigInteger('created_by')->nullable()
                    ->comment('FK landlord_users');
                $table->timestamps();
                $table->softDeletes();

                $table->index('target');
                $table->index('published_at');
            });
        }

        // -------------------------------------------------------------------------
        // Tenant Email Blasts
        // -------------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('tenant_email_blasts')) {
            Schema::connection('central')->create('tenant_email_blasts', function (Blueprint $table) {
                $table->id();
                $table->string('subject', 255);
                $table->text('body_html');
                $table->json('target_filter')->nullable()
                    ->comment('{"plan_ids":[],"product_ids":[],"statuses":[]}');
                $table->unsignedInteger('sent_count')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()
                    ->comment('FK landlord_users');
                $table->timestamps();
                $table->softDeletes();

                $table->index('sent_at');
            });
        }

        // -------------------------------------------------------------------------
        // Maintenance Windows
        // -------------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('maintenance_windows')) {
            Schema::connection('central')->create('maintenance_windows', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('message');
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->string('status', 20)->default('scheduled')
                    ->comment('scheduled | active | cancelled');
                $table->string('affected_tenants', 20)->default('all')
                    ->comment('all | specific');
                $table->json('affected_tenant_ids')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('starts_at');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('maintenance_windows');
        Schema::connection('central')->dropIfExists('tenant_email_blasts');
        Schema::connection('central')->dropIfExists('tenant_broadcasts');
        Schema::connection('central')->dropIfExists('feature_flag_experiments');
        Schema::connection('central')->dropIfExists('feature_flag_tenant_overrides');
        Schema::connection('central')->dropIfExists('feature_flags');
        Schema::connection('central')->dropIfExists('platform_webhook_delivery_logs');
        Schema::connection('central')->dropIfExists('platform_webhook_endpoints');
    }
};
