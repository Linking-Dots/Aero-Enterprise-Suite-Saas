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
        // API Gateway
        if (! Schema::connection('central')->hasTable('api_rate_limit_overrides')) {
            Schema::connection('central')->create('api_rate_limit_overrides', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->unique()->index();
                $table->unsignedInteger('requests_per_minute')->default(60);
                $table->unsignedInteger('requests_per_day')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('api_quota_overrides')) {
            Schema::connection('central')->create('api_quota_overrides', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->string('resource');
                $table->unsignedBigInteger('limit');
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'resource']);
            });
        }

        // Resource Provisioning
        if (! Schema::connection('central')->hasTable('db_server_pools')) {
            Schema::connection('central')->create('db_server_pools', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('host');
                $table->unsignedInteger('port')->default(3306);
                $table->unsignedInteger('max_connections')->default(100);
                $table->unsignedInteger('current_tenants')->default(0);
                $table->string('region_code')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('storage_backends')) {
            Schema::connection('central')->create('storage_backends', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('driver');
                $table->json('config')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('auto_scaling_rules')) {
            Schema::connection('central')->create('auto_scaling_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('resource_type');
                $table->string('metric');
                $table->decimal('scale_up_threshold', 8, 2);
                $table->decimal('scale_down_threshold', 8, 2);
                $table->unsignedTinyInteger('min_instances')->default(1);
                $table->unsignedTinyInteger('max_instances')->default(10);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        // Release Management
        if (! Schema::connection('central')->hasTable('release_versions')) {
            Schema::connection('central')->create('release_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('version')->unique();
                $table->string('channel')->default('stable');
                $table->text('notes')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->uuid('published_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_rollouts')) {
            Schema::connection('central')->create('tenant_rollouts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('version_id')->index();
                $table->string('tenant_id')->nullable()->index();
                $table->string('status')->default('pending');
                $table->timestamp('rolled_out_at')->nullable();
                $table->timestamp('rolled_back_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('changelog_entries')) {
            Schema::connection('central')->create('changelog_entries', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('version_id')->nullable()->index();
                $table->string('title');
                $table->text('body_md');
                $table->string('type')->default('feature');
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('changelog_entries');
        Schema::connection('central')->dropIfExists('tenant_rollouts');
        Schema::connection('central')->dropIfExists('release_versions');
        Schema::connection('central')->dropIfExists('auto_scaling_rules');
        Schema::connection('central')->dropIfExists('storage_backends');
        Schema::connection('central')->dropIfExists('db_server_pools');
        Schema::connection('central')->dropIfExists('api_quota_overrides');
        Schema::connection('central')->dropIfExists('api_rate_limit_overrides');
    }
};
