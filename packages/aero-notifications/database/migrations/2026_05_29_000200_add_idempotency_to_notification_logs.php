<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan 08 (aero-notifications) Task 2 of foundation 10/10 push.
 *
 * Adds idempotency_key column to notification_logs so the NotificationPipeline
 * can suppress duplicate sends when Horizon retries a SendEmailJob /
 * SendSmsJob after a transient failure.
 *
 * Without this, a tenant subscription renewal that fails on the first SMTP
 * attempt and gets retried by Horizon would send TWO emails — one for each
 * retry that eventually succeeded on the SMTP side. The idempotency key
 * (sha256 of recipient + channel + payload) gates the duplicate so the
 * second pipeline pass detects "already sent" and short-circuits.
 *
 * Composite unique index instead of plain unique() so the constraint scopes
 * to (channel, idempotency_key) — a Mail send and an SMS send may share the
 * same payload hash legitimately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('id');
            $table->unique(['channel', 'idempotency_key'], 'notification_logs_channel_idem_uq');
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropUnique('notification_logs_channel_idem_uq');
            $table->dropColumn('idempotency_key');
        });
    }
};
