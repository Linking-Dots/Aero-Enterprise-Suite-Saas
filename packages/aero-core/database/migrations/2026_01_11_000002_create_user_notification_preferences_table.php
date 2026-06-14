<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores user-level notification preferences for each event type and channel.
     * Allows users to opt-in/out of specific notifications per channel.
     */
    public function up(): void
    {
        // Canonical table is created by aero-notifications (2024_01_01_000002).
        // This duplicate must defer when it already exists, else migrate:fresh
        // collides in hosts loading both aero-core and aero-notifications.
        if (Schema::hasTable('user_notification_preferences')) {
            return;
        }

        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Event configuration
            $table->string('event_type'); // e.g., 'leave.requested', 'birthday', 'payroll.generated'
            $table->string('channel', 50); // database, mail, sms, push

            // Preference
            $table->boolean('enabled')->default(true);

            // Quiet hours (optional)
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();

            // Digest configuration
            $table->enum('digest_frequency', ['realtime', 'hourly', 'daily', 'weekly'])
                ->default('realtime');

            // Additional options
            $table->json('options')->nullable(); // Custom per-notification options

            $table->timestamps();

            // Unique constraint: one preference per user/event/channel combination
            $table->unique(['user_id', 'event_type', 'channel']);

            // Indexes
            $table->index('user_id');
            $table->index('event_type');
            $table->index('enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
