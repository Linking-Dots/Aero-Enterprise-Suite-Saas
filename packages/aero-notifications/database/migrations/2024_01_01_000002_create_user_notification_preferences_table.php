<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notification_preferences')) {
            return;
        }

        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('event_type', 100)->index();
            $table->string('channel', 20)->index();
            $table->boolean('enabled')->default(true);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->string('digest_frequency', 20)->nullable()->default('immediate');
            $table->json('options')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'event_type', 'channel']);
            $table->index(['user_id', 'channel']);
        });
    }

    public function down(): void { Schema::dropIfExists('user_notification_preferences'); }
};
