<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds BYOC (Bring Your Own Cloud) database credentials and
 * per-tenant encryption key ID to the tenants table.
 *
 * BYOC tenants provide their own MySQL/PostgreSQL credentials.
 * AEOS365 connects to their DB instead of provisioning one on our infrastructure.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            // Zero-Trust encryption
            $table->string('encryption_key_id')->nullable()->after('status')
                ->comment('Tenant-controlled KMS key ID. NULL = platform master key.');
            $table->string('encryption_driver')->nullable()->after('encryption_key_id')
                ->comment('Encryption driver: null=laravel, aws_kms, vault');

            // BYOC database credentials
            $table->boolean('byoc_enabled')->default(false)->after('encryption_driver');
            $table->string('byoc_db_driver', 10)->nullable()->after('byoc_enabled')
                ->comment('mysql or pgsql');
            $table->string('byoc_db_host')->nullable()->after('byoc_db_driver');
            $table->unsignedSmallInteger('byoc_db_port')->nullable()->after('byoc_db_host');
            $table->string('byoc_db_name')->nullable()->after('byoc_db_port');
            $table->text('byoc_db_username')->nullable()->after('byoc_db_name')
                ->comment('Encrypted with platform master key');
            $table->text('byoc_db_password')->nullable()->after('byoc_db_username')
                ->comment('Encrypted with platform master key');
            $table->string('byoc_db_ssl_mode')->nullable()->after('byoc_db_password')
                ->comment('require, verify-ca, verify-full, or null');
            $table->timestamp('byoc_validated_at')->nullable()->after('byoc_db_ssl_mode')
                ->comment('When AEOS365 last validated connectivity to the BYOC DB');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'encryption_key_id', 'encryption_driver',
                'byoc_enabled', 'byoc_db_driver', 'byoc_db_host', 'byoc_db_port',
                'byoc_db_name', 'byoc_db_username', 'byoc_db_password',
                'byoc_db_ssl_mode', 'byoc_validated_at',
            ]);
        });
    }
};
