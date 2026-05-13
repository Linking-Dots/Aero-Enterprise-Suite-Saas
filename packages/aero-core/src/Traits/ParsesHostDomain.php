<?php

declare(strict_types=1);

namespace Aero\Core\Traits;

use Illuminate\Http\Request;

/**
 * ParsesHostDomain Trait
 *
 * Provides domain parsing utilities for auto-detecting domain context
 * from URL structure without requiring .env configuration.
 *
 * Domain Pattern Recognition:
 * - admin.domain.com → Admin subdomain (central)
 * - domain.com → Platform/root domain (central)
 * - {tenant}.domain.com → Tenant subdomain
 *
 * Used by:
 * - IdentifyDomainContext middleware
 * - SetDatabaseConnectionFromDomain middleware
 * - InitializeTenancyIfNotCentral middleware
 * - SaaSTenantScope service
 */
trait ParsesHostDomain
{
    /**
     * Parse a host into subdomain and base domain parts.
     *
     * Examples:
     * - "admin.aeos365.test" → ['subdomain' => 'admin', 'baseDomain' => 'aeos365.test']
     * - "aeos365.test" → ['subdomain' => null, 'baseDomain' => 'aeos365.test']
     * - "tenant1.aeos365.test" → ['subdomain' => 'tenant1', 'baseDomain' => 'aeos365.test']
     * - "localhost" → ['subdomain' => null, 'baseDomain' => 'localhost']
     * - "admin.localhost" → ['subdomain' => 'admin', 'baseDomain' => 'localhost']
     * - "127.0.0.1" → ['subdomain' => null, 'baseDomain' => '127.0.0.1']
     *
     * @return array{subdomain: string|null, baseDomain: string}
     */
    protected function parseHost(string $host): array
    {
        // Remove port if present
        $host = preg_replace('/:\d+$/', '', $host);

        // Handle IP addresses specially (no subdomain possible)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ['subdomain' => null, 'baseDomain' => $host];
        }

        // Handle localhost specially
        if ($host === 'localhost') {
            return ['subdomain' => null, 'baseDomain' => $host];
        }

        $parts = explode('.', $host);

        // Single part (e.g., "localhost") - no subdomain
        if (count($parts) === 1) {
            return ['subdomain' => null, 'baseDomain' => $host];
        }

        // Two parts (e.g., "aeos365.test", "example.com") - no subdomain
        if (count($parts) === 2) {
            return ['subdomain' => null, 'baseDomain' => $host];
        }

        // Three+ parts - first part is subdomain, rest is base domain
        // e.g., "admin.aeos365.test" → subdomain="admin", baseDomain="aeos365.test"
        // e.g., "tenant.example.co.uk" → subdomain="tenant", baseDomain="example.co.uk"
        $subdomain = array_shift($parts);
        $baseDomain = implode('.', $parts);

        return ['subdomain' => $subdomain, 'baseDomain' => $baseDomain];
    }

    /**
     * Check if a host is on a central domain.
     *
     * Central domains are:
     * - Explicitly configured central domains (config/tenancy.php)
     * - Admin subdomain of configured domains: admin.domain.com
     * - localhost, 127.0.0.1
     *
     * Tenant domains are:
     * - Any subdomain not matching admin: tenant1.domain.com
     * - Custom domains registered in the domains table: customerbusiness.com
     *
     * IMPORTANT: Custom tenant domains (like customerbusiness.com) must NOT
     * be treated as central domains, even though they have no subdomain.
     * We check the domains table to distinguish custom tenant domains.
     */
    protected function isHostOnCentralDomain(string $host): bool
    {
        $parsed = $this->parseHost($host);

        // Admin subdomain is always central
        if ($parsed['subdomain'] === 'admin') {
            return true;
        }

        // Check against explicitly configured central domains
        $centralDomains = $this->getConfiguredCentralDomains();
        if (in_array($host, $centralDomains, true)) {
            return true;
        }

        // For root domains (no subdomain), check if it's a custom tenant domain
        // by looking it up in the tenant domains table
        if ($parsed['subdomain'] === null) {
            // If Platform package is active, check if this is a registered tenant domain
            if ($this->isRegisteredTenantDomain($host)) {
                return false; // It's a custom tenant domain, NOT central
            }

            // Default: root domain without subdomain is central (platform domain)
            return true;
        }

        // Any other subdomain = tenant domain
        return false;
    }

    /**
     * Get configured central domains from tenancy config.
     *
     * @return array<string>
     */
    protected function getConfiguredCentralDomains(): array
    {
        try {
            return config('tenancy.central_domains', ['localhost', '127.0.0.1']);
        } catch (\Throwable $e) {
            return ['localhost', '127.0.0.1'];
        }
    }

    /**
     * Check if a domain is registered as a tenant domain in the database.
     *
     * This is used to identify custom tenant domains (like customerbusiness.com)
     * that don't follow the subdomain pattern but are valid tenant domains.
     *
     * @param  string  $host  The domain to check
     * @return bool True if this domain is registered to a tenant
     */
    protected function isRegisteredTenantDomain(string $host): bool
    {
        // Only check if Platform package is installed
        if (! class_exists('Aero\Platform\Models\Domain')) {
            return false;
        }

        try {
            // Check if this domain exists in the tenant domains table
            $domainModel = 'Aero\\Platform\\Models\\Domain';
            return $domainModel::where('domain', $host)->exists();
        } catch (\Throwable $e) {
            // Database not available (during install, testing, etc.)
            return false;
        }
    }

    /**
     * Check if a host is the admin subdomain.
     */
    protected function isHostAdminDomain(string $host): bool
    {
        $parsed = $this->parseHost($host);

        return $parsed['subdomain'] === 'admin';
    }

    /**
     * Check if a host is the platform/root domain (no subdomain).
     *
     * Note: This only checks subdomain structure. For custom tenant domains
     * (root domains registered to tenants), use isHostOnCentralDomain() instead.
     */
    protected function isHostPlatformDomain(string $host): bool
    {
        $parsed = $this->parseHost($host);

        // Check if it's the actual platform domain (no subdomain + is central)
        if ($parsed['subdomain'] !== null) {
            return false;
        }

        // Must also not be a custom tenant domain
        return $this->isHostOnCentralDomain($host);
    }

    /**
     * Check if a host is a tenant domain (subdomain or custom domain).
     *
     * Tenant domains include:
     * - Subdomain-based: tenant1.domain.com, acme.aeos365.test
     * - Custom domains: customerbusiness.com (registered in domains table)
     */
    protected function isHostTenantDomain(string $host): bool
    {
        // A host is a tenant domain if it's NOT a central domain
        return ! $this->isHostOnCentralDomain($host);
    }

    /**
     * Get the platform/base domain from a host.
     * Strips any subdomain and returns the root domain.
     *
     * Examples:
     * - "admin.aeos365.test" → "aeos365.test"
     * - "tenant1.aeos365.test" → "aeos365.test"
     * - "aeos365.test" → "aeos365.test"
     */
    protected function getPlatformDomainFromHost(string $host): string
    {
        $parsed = $this->parseHost($host);

        return $parsed['baseDomain'];
    }

    /**
     * Get the admin domain for a given host.
     *
     * Example: "aeos365.test" → "admin.aeos365.test"
     */
    protected function getAdminDomainFromHost(string $host): string
    {
        $baseDomain = $this->getPlatformDomainFromHost($host);

        return 'admin.'.$baseDomain;
    }

    /**
     * Build a tenant domain for a given tenant slug.
     *
     * Example: getTenantDomainFromHost("aeos365.test", "acme") → "acme.aeos365.test"
     */
    protected function getTenantDomainFromHost(string $host, string $tenantSlug): string
    {
        $baseDomain = $this->getPlatformDomainFromHost($host);

        return $tenantSlug.'.'.$baseDomain;
    }

    /**
     * Get the subdomain from a host (tenant slug).
     * Returns null if no subdomain or if it's the admin subdomain.
     *
     * Example: "acme.aeos365.test" → "acme"
     */
    protected function getTenantSlugFromHost(string $host): ?string
    {
        $parsed = $this->parseHost($host);

        if ($parsed['subdomain'] === null || $parsed['subdomain'] === 'admin') {
            return null;
        }

        return $parsed['subdomain'];
    }

    /**
     * Get the current host from request, with port stripped.
     */
    protected function getCurrentHost(?Request $request = null): string
    {
        $request = $request ?? request();

        if (! $request) {
            return 'localhost';
        }

        return preg_replace('/:\d+$/', '', $request->getHost());
    }
}
