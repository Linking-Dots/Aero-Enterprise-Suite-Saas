<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores admin-level notification system settings.
     * Controls global behavior, defaults, and channel configurations.
     */
    public function up(): void
    {
        // Canonical table is created by aero-notifications (2024_01_01_000001).
        // This duplicate must defer when it already exists, else migrate:fresh
        // collides in hosts loading both aero-core and aero-notifications.
        if (Schema::hasTable('notification_settings')) {
            return;
        }

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'channels.email.enabled'
            $table->json('value'); // Flexible JSON storage for any setting type
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('notification_settings')->insert([
            [
                'key' => 'channels.email.enabled',
                'value' => json_encode(true),
                'description' => 'Enable/disable email notifications globally',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'channels.sms.enabled',
                'value' => json_encode(false),
                'description' => 'Enable/disable SMS notifications globally',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'channels.push.enabled',
                'value' => json_encode(false),
                'description' => 'Enable/disable push notifications globally',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'channels.database.enabled',
                'value' => json_encode(true),
                'description' => 'Enable/disable in-app notifications globally',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'retry.max_attempts',
                'value' => json_encode(3),
                'description' => 'Maximum retry attempts for failed notifications',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'retry.backoff_minutes',
                'value' => json_encode([5, 15, 60]), // Exponential backoff
                'description' => 'Retry backoff intervals in minutes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
