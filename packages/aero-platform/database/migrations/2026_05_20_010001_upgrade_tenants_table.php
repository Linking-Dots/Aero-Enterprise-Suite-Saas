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
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            // Add only missing lifecycle columns (BYOC already exists from 2026_05_14 migration)
            if (! Schema::connection('central')->hasColumn('tenants', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('status');
            }
            if (! Schema::connection('central')->hasColumn('tenants', 'suspension_reason')) {
                $table->string('suspension_reason')->nullable()->after('suspended_at');
            }
            if (! Schema::connection('central')->hasColumn('tenants', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('suspension_reason');
            }
            if (! Schema::connection('central')->hasColumn('tenants', 'frozen_at')) {
                $table->timestamp('frozen_at')->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void {}
};
