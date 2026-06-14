<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_settings')) {
            return;
        }

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->index();
            $table->json('value')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->index(['key', 'tenant_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('notification_settings'); }
};
