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
        if (! Schema::connection('central')->hasTable('dpa_templates')) {
            Schema::connection('central')->create('dpa_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('version')->unique();
                $table->string('title');
                $table->longText('content_md');
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_dpa_signings')) {
            Schema::connection('central')->create('tenant_dpa_signings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->uuid('template_id')->index();
                $table->string('signed_by_name');
                $table->string('signed_by_email');
                $table->timestamp('signed_at');
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('subprocessors')) {
            Schema::connection('central')->create('subprocessors', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('category')->nullable();
                $table->string('country')->nullable();
                $table->text('purpose')->nullable();
                $table->string('dpa_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tos_versions')) {
            Schema::connection('central')->create('tos_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('version')->unique();
                $table->longText('content_md');
                $table->timestamp('published_at')->nullable();
                $table->boolean('requires_re_acceptance')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('privacy_versions')) {
            Schema::connection('central')->create('privacy_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('version')->unique();
                $table->longText('content_md');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('dsar_requests')) {
            Schema::connection('central')->create('dsar_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->string('requester_email');
                $table->string('request_type')->default('access');
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('fulfilled_at')->nullable();
                $table->uuid('fulfilled_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('dsar_requests');
        Schema::connection('central')->dropIfExists('privacy_versions');
        Schema::connection('central')->dropIfExists('tos_versions');
        Schema::connection('central')->dropIfExists('subprocessors');
        Schema::connection('central')->dropIfExists('tenant_dpa_signings');
        Schema::connection('central')->dropIfExists('dpa_templates');
    }
};
