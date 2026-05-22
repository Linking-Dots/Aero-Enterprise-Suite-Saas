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
        if (! Schema::connection('central')->hasTable('regions')) {
            Schema::connection('central')->create('regions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('db_host')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->json('cdn_config')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_region_assignments')) {
            Schema::connection('central')->create('tenant_region_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->unique()->index();
                $table->uuid('region_id')->index();
                $table->uuid('assigned_by')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_region_assignments');
        Schema::connection('central')->dropIfExists('regions');
    }
};
