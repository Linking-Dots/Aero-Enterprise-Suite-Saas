<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Unit\Http;

use Aero\Auth\Http\Controllers\Auth\ImpersonationController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 05 (aero-auth) Task 3 — impersonation open-redirect regression pin.
 *
 * Phase 1 audit found ImpersonationController::handle() did:
 *   $redirectUrl = $impersonationToken->redirect_url ?? '/dashboard';
 *   return redirect($redirectUrl)->...
 *
 * An attacker who forged a token with redirect_url='https://evil.com'
 * would have the impersonated user redirected externally — a phishing
 * vector wrapped inside an apparently-legitimate platform-admin flow.
 *
 * Fix: validate via SafeRedirect::isSafePath() before passing to redirect().
 * Falls back to /dashboard with a Log::warning on rejection.
 */
class ImpersonationOpenRedirectTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(ImpersonationController::class))->getFileName());
    }

    public function test_impersonation_imports_safe_redirect_helper(): void
    {
        $this->assertStringContainsString(
            'use Aero\\Core\\Support\\SafeRedirect;',
            $this->source(),
            'ImpersonationController must import SafeRedirect (Plan 05 T3).'
        );
    }

    public function test_redirect_url_passes_through_is_safe_path(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/SafeRedirect::isSafePath\(/',
            $source,
            'handle() must validate the token redirect_url via SafeRedirect::isSafePath() '.
            'before calling redirect() — otherwise an attacker-supplied redirect_url '.
            'turns the impersonation flow into a phishing redirect.'
        );
    }

    public function test_unsafe_redirect_falls_back_to_dashboard(): void
    {
        $source = $this->source();

        // Pin the fallback so a refactor can't accidentally remove it
        $this->assertMatchesRegularExpression(
            "/isSafePath\(\\\$requestedUrl\)\s*\?\s*\\\$requestedUrl\s*:\s*['\"]\/dashboard['\"]/",
            $source,
            'Unsafe redirect_url must fall back to /dashboard, not abort or accept.'
        );
    }

    public function test_unsafe_redirect_attempt_is_logged(): void
    {
        $source = $this->source();

        // Audit signal — security team needs to see attempted open-redirect tokens
        $this->assertMatchesRegularExpression(
            "/Log::warning\(.*Impersonation\s+token\s+redirect_url\s+was\s+unsafe/",
            $source,
            'When the fallback fires, log a warning so security can investigate '.
            'where the unsafe token came from.'
        );
    }
}
