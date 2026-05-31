<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sso_configurations')) {
            return;
        }
        Schema::create('sso_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // saml|oidc|social_google|social_microsoft|social_github|social_apple|magic_link|passkeys|scim
            $table->boolean('is_enabled')->default(false);
            $table->json('config')->nullable();
            $table->string('scim_token_hash')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('last_test_passed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_configurations');
    }
};
