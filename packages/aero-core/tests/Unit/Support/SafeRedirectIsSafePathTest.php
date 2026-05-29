<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Unit\Support;

use Aero\Core\Support\SafeRedirect;
use PHPUnit\Framework\TestCase;

/**
 * Plan 05 (aero-auth) Task 3 — SafeRedirect::isSafePath() coverage.
 *
 * Used by ImpersonationController and any other surface that accepts
 * a redirect target from untrusted input. Pins the documented attack
 * vectors as concrete denial test cases so a future refactor can't
 * accidentally re-open the open-redirect hole.
 */
class SafeRedirectIsSafePathTest extends TestCase
{
    /**
     * @dataProvider safePathProvider
     */
    public function test_safe_paths_are_accepted(string $path): void
    {
        $this->assertTrue(SafeRedirect::isSafePath($path),
            "Path '{$path}' should be considered safe but was rejected.");
    }

    public static function safePathProvider(): array
    {
        return [
            ['/'],
            ['/dashboard'],
            ['/admin/users'],
            ['/admin/users?page=2'],
            ['/admin/users#section'],
            ['/path/with/many/segments'],
            ['/path-with-dashes'],
            ['/path_with_underscores'],
            ['/api/v1/endpoint'],
        ];
    }

    /**
     * @dataProvider unsafePathProvider
     */
    public function test_unsafe_paths_are_rejected(?string $path, string $reason): void
    {
        $this->assertFalse(SafeRedirect::isSafePath($path),
            "Path '{$path}' should be REJECTED: {$reason}");
    }

    public static function unsafePathProvider(): array
    {
        return [
            [null,                          'null'],
            ['',                            'empty string'],
            ['dashboard',                   'no leading slash'],
            ['//evil.com/phish',            'protocol-relative URL (browsers treat as evil.com)'],
            ['//evil.com',                  'protocol-relative URL'],
            ['/\\evil.com',                 'encoded backslash protocol-relative trick'],
            ['https://evil.com',            'full URL scheme'],
            ['http://evil.com',             'full URL scheme'],
            ['javascript:alert(1)',         'javascript: scheme'],
            ['/path://evil.com',            'embedded :// scheme smuggle'],
            ["/path\\to\\evil",             'Windows backslashes (some browsers normalize)'],
            ["/path\nwith\nnewlines",       'response splitting via newline'],
            ["/path\rwith\rcr",             'response splitting via carriage return'],
            ["/path\x00null",               'null byte injection'],
            ["/path\twith\ttab",            'tab control character'],
        ];
    }

    public function test_method_handles_null_input(): void
    {
        // Pin the null-safety so callers can pass `$model->redirect_url ?? null` safely
        $this->assertFalse(SafeRedirect::isSafePath(null));
    }
}
