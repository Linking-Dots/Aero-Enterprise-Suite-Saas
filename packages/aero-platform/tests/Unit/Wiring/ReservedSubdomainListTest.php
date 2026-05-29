<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Wiring;

use PHPUnit\Framework\TestCase;

/**
 * Plan 03 (aero-platform) Task 6 — reserved subdomain list regression pin.
 *
 * Phase 1 audit found the previous reserved list was just ['admin', 'www', 'api'],
 * dangerously incomplete. A tenant could register the subdomain 'mail' and
 * intercept platform mail DNS — or 'static' and serve malicious assets, or
 * 'horizon' and access the queue dashboard URL, etc.
 *
 * This file pins:
 *   - the list now includes every platform-infrastructure name we know about
 *   - every subdomain validation site references the config
 *
 * Full HTTP test (POST /register with subdomain='mail' returns 422) lives in
 * the host repo's feature suite.
 */
class ReservedSubdomainListTest extends TestCase
{
    /**
     * Read the reserved_subdomains array from config/tenancy.php as raw text.
     *
     * We can't `require` the config in a unit-test context because it calls
     * Laravel helpers (database_path) that need a booted app. Parse the array
     * declaration via regex instead — slow path is fine for ~50 entries.
     */
    private function loadReservedList(): array
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/config/tenancy.php');

        // Match the 'reserved_subdomains' => [ ... ], block
        if (! preg_match("/'reserved_subdomains'\s*=>\s*\[(.*?)\],\s*\/\*/s", $source, $m)) {
            $this->fail("Could not locate 'reserved_subdomains' block in config/tenancy.php");
        }

        // Extract every quoted string literal from the block
        preg_match_all("/'([a-z0-9_-]+)'/", $m[1], $matches);

        return $matches[1];
    }

    public function test_reserved_list_blocks_mail_infrastructure(): void
    {
        $reserved = $this->loadReservedList();

        foreach (['mail', 'smtp', 'imap', 'pop', 'webmail'] as $needle) {
            $this->assertContains($needle, $reserved,
                "Mail infrastructure subdomain '{$needle}' must be reserved to prevent ".
                'tenant hijacking of platform email DNS.');
        }
    }

    public function test_reserved_list_blocks_cdn_static_assets(): void
    {
        $reserved = $this->loadReservedList();

        foreach (['cdn', 'static', 'media', 'assets', 'img'] as $needle) {
            $this->assertContains($needle, $reserved,
                "CDN / static subdomain '{$needle}' must be reserved.");
        }
    }

    public function test_reserved_list_blocks_laravel_ui_tools(): void
    {
        $reserved = $this->loadReservedList();

        // Horizon at horizon.tenant.com would leak the queue UI URL
        foreach (['horizon', 'telescope', 'pulse'] as $needle) {
            $this->assertContains($needle, $reserved,
                "Laravel UI tool subdomain '{$needle}' must be reserved.");
        }
    }

    public function test_reserved_list_blocks_auth_surfaces(): void
    {
        $reserved = $this->loadReservedList();

        foreach (['login', 'logout', 'sso', 'oauth', 'auth', 'register'] as $needle) {
            $this->assertContains($needle, $reserved,
                "Auth surface subdomain '{$needle}' must be reserved.");
        }
    }

    public function test_legacy_minimal_list_still_present(): void
    {
        $reserved = $this->loadReservedList();

        foreach (['admin', 'www', 'api'] as $needle) {
            $this->assertContains($needle, $reserved,
                "Legacy reserved name '{$needle}' must remain in the list (backward compat).");
        }
    }

    public function test_registration_details_request_references_reserved_list(): void
    {
        $path = dirname(__DIR__, 3).'/src/Http/Requests/RegistrationDetailsRequest.php';
        $source = file_get_contents($path);

        $this->assertStringContainsString(
            "Rule::notIn(config('tenancy.reserved_subdomains'",
            $source,
            'RegistrationDetailsRequest must call Rule::notIn against the reserved list.'
        );
    }

    public function test_check_registration_subdomain_request_references_reserved_list(): void
    {
        $path = dirname(__DIR__, 3).'/src/Http/Requests/CheckRegistrationSubdomainRequest.php';
        $source = file_get_contents($path);

        $this->assertStringContainsString(
            "Rule::notIn(config('tenancy.reserved_subdomains'",
            $source,
            'The pre-flight availability probe must enforce the same reserved list as ".
            "the actual registration POST — otherwise the UI shows green for ".
            "reserved names then fails at submit.'
        );
    }

    public function test_tenant_controller_references_reserved_list(): void
    {
        $path = dirname(__DIR__, 3).'/src/Http/Controllers/TenantController.php';
        $source = file_get_contents($path);

        $count = preg_match_all("/Rule::notIn\(config\('tenancy\.reserved_subdomains'/", $source);
        $this->assertGreaterThanOrEqual(2, $count,
            "TenantController must reference the reserved list in BOTH store() and ".
            "checkSubdomain() — found {$count}.");
    }
}
