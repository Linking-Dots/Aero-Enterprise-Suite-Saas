<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Unit\Http;

use Aero\Auth\Http\Controllers\Auth\PasswordResetLinkController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 05 (aero-auth) Task 1 — password reset hardening regression pin.
 *
 * Phase 1 audit found PasswordResetLinkController had:
 *   - NO rate limit (anyone could spam any email at any rate)
 *   - Account enumeration (distinct success/failure responses leaked existence)
 *
 * Both gaps now closed:
 *   - RateLimiter per-email (5/hour) AND per-IP (10/10min)
 *   - uniformResponse() returns same status/message regardless of outcome
 *
 * Full HTTP test (POST /forgot-password 6 times → 429) lives in host repo.
 * This file pins the structural contract.
 */
class PasswordResetLinkControllerTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(PasswordResetLinkController::class))->getFileName());
    }

    public function test_controller_uses_rate_limiter(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'use Illuminate\\Support\\Facades\\RateLimiter;',
            $source,
            'PasswordResetLinkController must import RateLimiter (Plan 05 T1).'
        );
    }

    public function test_per_email_rate_limit_applied(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            "/RateLimiter::tooManyAttempts\(\\\$perEmailKey/",
            $source,
            'A per-email rate limit must be checked before sending the link.'
        );

        $this->assertMatchesRegularExpression(
            "/RateLimiter::hit\(\\\$perEmailKey\s*,\s*\d+/",
            $source,
            'Per-email key must be hit() with a decay seconds value.'
        );
    }

    public function test_per_ip_rate_limit_applied(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            "/RateLimiter::tooManyAttempts\(\\\$perIpKey/",
            $source,
            'A per-IP rate limit must close the cross-email-enumeration vector.'
        );
    }

    public function test_uniform_response_helper_exists(): void
    {
        $r = new ReflectionClass(PasswordResetLinkController::class);

        $this->assertTrue($r->hasMethod('uniformResponse'),
            'uniformResponse() helper must exist (Plan 05 T1) to return identical '.
            'response shape regardless of whether the email exists.');

        $method = $r->getMethod('uniformResponse');
        $this->assertTrue($method->isPrivate(),
            'uniformResponse() must be private — internal helper, not API.');
    }

    public function test_no_distinct_error_path_for_invalid_email(): void
    {
        $source = $this->source();

        // The old code used `withErrors(['email' => ...])` which leaked
        // account existence. Verify that's gone — store() now uses ONLY
        // uniformResponse() for all outcomes.
        $this->assertDoesNotMatchRegularExpression(
            "/withErrors\(\s*\[\s*['\"]email['\"]/",
            $source,
            'store() must NOT use withErrors([email => ...]) — that leaks account '.
            'existence. Return uniformResponse() instead.'
        );
    }

    public function test_uniform_message_does_not_confirm_existence(): void
    {
        $source = $this->source();

        // Pin the wording — must use "If an account ... exists" phrasing,
        // never "We sent" or "Email sent" which would confirm existence.
        $this->assertStringContainsString(
            "If an account with that email exists",
            $source,
            'Uniform message must use "if an account exists" phrasing to avoid '.
            'leaking existence on success path.'
        );
    }

    public function test_email_lowercased_before_rate_limit_key(): void
    {
        $source = $this->source();

        // Email must be lowercased before being hashed into the rate limit key —
        // otherwise an attacker could bypass by varying case.
        $this->assertMatchesRegularExpression(
            "/strtolower\(.*request->email/",
            $source,
            "Email must be lowercased before deriving the rate limit key — ".
            "case variation must NOT bypass the per-email limit."
        );
    }
}
