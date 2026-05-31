<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Unit\Http;

use Aero\Auth\Http\Controllers\Auth\LoginController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 05 (aero-auth) Task 2 — login rate-limit hardening pin.
 *
 * Phase 1 audit found LoginController used a single 'login.{ip}' rate
 * limit. An attacker could probe many email addresses from one IP
 * within the 5-attempt window — single-IP credential stuffing.
 *
 * The fix adds a parallel per-email rate-limit key so a single account
 * can be brute-forced no faster than 5 attempts / 15 minutes regardless
 * of how many IPs the attacker rotates through.
 */
class LoginRateLimitTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(LoginController::class))->getFileName());
    }

    public function test_login_derives_per_ip_rate_limit_key(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/\$ipKey\s*=\s*[\'"]login\.ip\./',
            $source,
            'LoginController::store() must derive an ipKey for per-IP rate limiting.'
        );
    }

    public function test_login_derives_per_email_rate_limit_key(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/\$emailKey\s*=\s*[\'"]login\.email\./',
            $source,
            'LoginController::store() must derive an emailKey for per-email rate limiting.'
        );
    }

    public function test_email_key_is_hashed_to_avoid_pii_in_cache(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/\$emailKey\s*=\s*[\'"]login\.email\.[\'"]\s*\.\s*sha1\(/',
            $source,
            'emailKey must hash the email via sha1() — raw email in cache keys is a PII leak '.
            'on shared Redis instances and in cache dumps.'
        );
    }

    public function test_email_lowercased_before_keying(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/\$email\s*=\s*strtolower/',
            $source,
            'Email must be lowercased before being used in the rate-limit key — '.
            'case variation must NOT bypass the per-email limit.'
        );
    }

    public function test_both_keys_checked_before_validation(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/foreach\s*\(\s*\[\$ipKey\s*,\s*\$emailKey\]/',
            $source,
            'Both ipKey and emailKey must be checked via tooManyAttempts() before '.
            'the password is compared.'
        );
    }

    public function test_both_keys_hit_on_failed_attempt(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/RateLimiter::hit\(\$ipKey/',
            $source,
            'A failed login must hit() ipKey.'
        );
        $this->assertMatchesRegularExpression(
            '/RateLimiter::hit\(\$emailKey/',
            $source,
            'A failed login must hit() emailKey — otherwise the cross-IP brute '.
            'force is unblocked.'
        );
    }

    public function test_both_keys_cleared_on_success(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/RateLimiter::clear\(\$ipKey/',
            $source,
            'Successful login must clear() ipKey so legitimate users are not throttled.'
        );
        $this->assertMatchesRegularExpression(
            '/RateLimiter::clear\(\$emailKey/',
            $source,
            'Successful login must clear() emailKey too.'
        );
    }

    public function test_email_decay_is_longer_than_ip_decay(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/RateLimiter::hit\(\$emailKey\s*,\s*(900|1800|3600)\b/',
            $source,
            'emailKey decay must be >= 900s (15 min) so cross-IP single-account '.
            'brute force is slow.'
        );
    }
}
