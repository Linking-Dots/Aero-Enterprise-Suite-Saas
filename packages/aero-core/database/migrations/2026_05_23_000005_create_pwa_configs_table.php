<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pwa_configs')) {
            return;
        }

        Schema::create('pwa_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('pwa_enabled')->default(false);
            $table->string('display_name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('theme_color', 7)->nullable();
            $table->string('background_color', 7)->nullable();
            $table->string('display_mode')->default('standalone');
            $table->string('icon_path')->nullable();
            $table->boolean('push_enabled')->default(false);
            $table->string('vapid_public_key')->nullable();
            $table->text('vapid_private_key')->nullable(); // encrypted
            $table->boolean('mobile_app_enabled')->default(false);
            $table->string('android_package')->nullable();
            $table->string('ios_bundle_id')->nullable();
            $table->json('deep_link_schemes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_configs');
    }
};
