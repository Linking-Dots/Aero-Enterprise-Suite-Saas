<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P-10: Infrastructure & Security tables.
 *
 * Tables created:
 *  1. tenant_custom_domains
 *  2. tenant_brandings
 *  3. backup_schedules
 *  4. backup_records
 *  5. status_page_components
 *  6. status_incidents
 *  7. status_page_postmortems
 *  8. security_incidents
 *  9. pentest_reports
 * 10. staff_ip_allowlist
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        // 1. tenant_custom_domains
        if (! Schema::connection('central')->hasTable('tenant_custom_domains')) {
            Schema::connection('central')->create('tenant_custom_domains', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->string('domain')->unique();
                $table->string('status')->default('pending'); // pending|verified|failed
                $table->string('ssl_status')->default('none'); // none|provisioning|active|expired
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('ssl_expires_at')->nullable();
                $table->string('dns_txt_record')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('tenant_id');
                $table->index('status');
            });
        }

        // 2. tenant_brandings
        if (! Schema::connection('central')->hasTable('tenant_brandings')) {
            Schema::connection('central')->create('tenant_brandings', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id')->unique();
                $table->string('logo_path')->nullable();
                $table->string('favicon_path')->nullable();
                $table->string('primary_color', 7)->default('#3B82F6');
                $table->string('secondary_color', 7)->default('#1E40AF');
                $table->string('custom_css_path')->nullable();
                $table->string('email_from_name')->nullable();
                $table->string('email_from_address')->nullable();
                $table->string('dkim_selector')->nullable();
                $table->text('dkim_private_key')->nullable(); // encrypted
                $table->timestamps();
            });
        }

        // 3. backup_schedules
        if (! Schema::connection('central')->hasTable('backup_schedules')) {
            Schema::connection('central')->create('backup_schedules', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id')->nullable();
                $table->string('frequency'); // daily|weekly|monthly
                $table->string('time_of_day', 5); // HH:MM
                $table->unsignedSmallInteger('retention_days')->default(30);
                $table->string('storage_backend_code');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_run_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('tenant_id');
                $table->index('is_active');
            });
        }

        // 4. backup_records
        if (! Schema::connection('central')->hasTable('backup_records')) {
            Schema::connection('central')->create('backup_records', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->foreignId('schedule_id')->nullable()->constrained('backup_schedules')->nullOnDelete();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('storage_path');
                $table->string('storage_backend');
                $table->string('status')->default('running'); // running|completed|failed
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index('tenant_id');
                $table->index('status');
            });
        }

        // 5. status_page_components
        if (! Schema::connection('central')->hasTable('status_page_components')) {
            Schema::connection('central')->create('status_page_components', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->string('status')->default('operational'); // operational|degraded|partial_outage|major_outage
                $table->unsignedSmallInteger('order_index')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 6. status_incidents
        if (! Schema::connection('central')->hasTable('status_incidents')) {
            Schema::connection('central')->create('status_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('status')->default('investigating'); // investigating|identified|monitoring|resolved
                $table->string('impact')->default('minor'); // minor|major|critical
                $table->json('component_ids')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('resolved_at')->nullable();
                $table->json('updates')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status');
            });
        }

        // 7. status_page_postmortems
        if (! Schema::connection('central')->hasTable('status_page_postmortems')) {
            Schema::connection('central')->create('status_page_postmortems', function (Blueprint $table) {
                $table->id();
                $table->foreignId('incident_id')->constrained('status_incidents')->cascadeOnDelete();
                $table->string('title');
                $table->longText('content_md');
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
                $table->softDeletes();

                $table->index('incident_id');
            });
        }

        // 8. security_incidents
        if (! Schema::connection('central')->hasTable('security_incidents')) {
            Schema::connection('central')->create('security_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('severity')->default('medium'); // low|medium|high|critical
                $table->text('description');
                $table->string('status')->default('open'); // open|investigating|resolved
                $table->json('affected_tenants')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('notified_tenants_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('severity');
                $table->index('status');
            });
        }

        // 9. pentest_reports
        if (! Schema::connection('central')->hasTable('pentest_reports')) {
            Schema::connection('central')->create('pentest_reports', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('conducted_by');
                $table->date('conducted_at');
                $table->json('severity_summary')->nullable();
                $table->string('file_path');
                $table->boolean('is_public')->default(false);
                $table->json('shared_with_tenants')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 10. staff_ip_allowlist
        if (! Schema::connection('central')->hasTable('staff_ip_allowlist')) {
            Schema::connection('central')->create('staff_ip_allowlist', function (Blueprint $table) {
                $table->id();
                $table->string('ip_cidr'); // CIDR notation e.g. 10.0.0.0/24 or 1.2.3.4/32
                $table->string('label');
                $table->unsignedBigInteger('created_by');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('staff_ip_allowlist');
        Schema::connection('central')->dropIfExists('pentest_reports');
        Schema::connection('central')->dropIfExists('security_incidents');
        Schema::connection('central')->dropIfExists('status_page_postmortems');
        Schema::connection('central')->dropIfExists('status_incidents');
        Schema::connection('central')->dropIfExists('status_page_components');
        Schema::connection('central')->dropIfExists('backup_records');
        Schema::connection('central')->dropIfExists('backup_schedules');
        Schema::connection('central')->dropIfExists('tenant_brandings');
        Schema::connection('central')->dropIfExists('tenant_custom_domains');
    }
};
