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
        if (! Schema::connection('central')->hasTable('license_keys')) {
            Schema::connection('central')->create('license_keys', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('key')->unique();
                $table->json('product_codes');
                $table->string('plan_code')->nullable();
                $table->unsignedInteger('max_activations')->default(1);
                $table->unsignedInteger('activation_count')->default(0);
                $table->string('issued_to_name');
                $table->string('issued_to_email');
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::connection('central')->hasTable('license_activations')) {
            Schema::connection('central')->create('license_activations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('license_id')->index();
                $table->string('install_id')->unique();
                $table->string('domain')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('last_ping_at')->nullable();
                $table->timestamp('deactivated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('license_activations');
        Schema::connection('central')->dropIfExists('license_keys');
    }
};
