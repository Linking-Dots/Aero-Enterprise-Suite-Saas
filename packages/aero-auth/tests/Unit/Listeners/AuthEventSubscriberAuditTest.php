<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Unit\Listeners;

use Aero\Auth\Listeners\AuthEventSubscriber;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 05 (aero-auth) Task 5 — auth event audit channel consolidation.
 *
 * Phase 1 audit found AuthEventSubscriber::logActivity() used Spatie's
 * `activity()` helper, while LoginController used AuditService — same
 * conceptual event landing in two different tables.
 *
 * The fix routes logActivity() through AuditServiceInterface so every
 * auth event (login, logout, failed, lockout, registered, password_reset,
 * verified, current_device_logout, other_device_logout) lands in the
 * structured audit_logs table where compliance queries live.
 *
 * The per-handler Log::channel('auth') calls remain for SIEM tailing.
 */
class AuthEventSubscriberAuditTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(AuthEventSubscriber::class))->getFileName());
    }

    public function test_log_activity_no_longer_uses_spatie_activity(): void
    {
        $source = $this->source();

        // The helper's IMPLEMENTATION must not call activity()->...->log()
        // Look for the helper body specifically (between its docblock and
        // the closing brace).
        if (! preg_match('/protected function logActivity\b.*?^\s*\}/sm', $source, $m)) {
            $this->fail('Could not locate logActivity() method body.');
        }
        $body = $m[0];

        $this->assertDoesNotMatchRegularExpression(
            '/\bactivity\s*\(\s*\)\s*\n?\s*->\s*causedBy/',
            $body,
            'logActivity() must NOT call activity()->causedBy() — Plan 05 T5 swaps '.
            'this for AuditServiceInterface::log() so auth events land in the same '.
            'audit_logs table as other business events.'
        );
    }

    public function test_log_activity_uses_audit_service_interface(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/app\(\s*\\\\?Aero\\\\Contracts\\\\AuditServiceInterface::class\s*\)/',
            $source,
            'logActivity() must resolve AuditServiceInterface from the container.'
        );

        $this->assertMatchesRegularExpression(
            '/->log\(\s*event:/',
            $source,
            'logActivity() must call ->log() with named event: argument matching '.
            'the AuditServiceInterface signature.'
        );
    }

    public function test_event_is_namespaced_under_auth(): void
    {
        $source = $this->source();

        // All event keys should be prefixed with 'auth.' to mark provenance —
        // distinguishes auth events from feature-package events in audit_logs
        $this->assertMatchesRegularExpression(
            "/event:\s*['\"]auth\\.['\"]?\s*\.\s*\\\$event/",
            $source,
            "Event key must be 'auth.'.\$event so audit_logs.event column is queryable ".
            "as `WHERE event LIKE 'auth.%'`."
        );
    }

    public function test_failure_path_still_logs_to_channel_fallback(): void
    {
        $source = $this->source();

        // If AuditService throws, we still want the signal — fall back to Log
        $this->assertMatchesRegularExpression(
            '/Log::warning\(.*Failed to log auth activity via AuditService/',
            $source,
            'When AuditService throws, the failure must be logged so the signal is '.
            'not silently swallowed.'
        );
    }
}
