<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * This table tracks all notification deliveries across all channels.
     * Enables monitoring, retry logic, and delivery analytics.
     */
    public function up(): void
    {
        // The canonical notification_logs table is created by
        // aero-notifications (2024_01_01_000000) — the only NotificationLog
        // model (Aero\Notifications\Models\NotificationLog) uses that schema.
        // This (unused) duplicate must defer when that table already exists,
        // matching the guard aero-platform's create_notification_logs migration
        // already uses. Without this guard `migrate:fresh` collides in any host
        // that loads both aero-core and aero-notifications.
        if (Schema::hasTable('notification_logs')) {
            return;
        }

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            // Notifiable entity (user, employee, etc.)
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->index(['notifiable_type', 'notifiable_id']);

            // Notification details
            $table->string('notification_type'); // Full class name
            $table->string('event_type')->nullable(); // e.g., 'leave.requested'
            $table->string('channel', 50); // database, mail, sms, push, slack

            // Delivery status
            $table->enum('status', ['pending', 'sent', 'failed', 'retrying'])
                ->default('pending');

            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('retry_at')->nullable();

            // Failure tracking
            $table->text('failure_reason')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(3);

            // Metadata
            $table->json('metadata')->nullable(); // Recipients, subject, tracking IDs, etc.

            // User interaction tracking (for in-app notifications)
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('notification_type');
            $table->index('event_type');
            $table->index('channel');
            $table->index('status');
            $table->index('sent_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
}
