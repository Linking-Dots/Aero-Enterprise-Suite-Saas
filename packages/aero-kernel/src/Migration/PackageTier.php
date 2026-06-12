<?php

declare(strict_types=1);

namespace Aero\Kernel\Migration;

/**
 * Single source of truth for Phase-4 migration routing by package tier.
 *
 * Each aero package declares `extra.aero.tier` (platform|core|sharable|product) in its
 * composer.json. The install context decides which tiers run against which database:
 *
 *   central    (SaaS landlord) = platform + sharable
 *   tenant     (SaaS per-tenant) = core + sharable + product (product gated to SUBSCRIBED)
 *   standalone (single DB)       = core + sharable + product (product = PURCHASED/installed)
 *
 * The product *selection* (subscribed vs installed) is applied on top of the tier by the
 * caller — this class only answers "does this package's tier belong in this context?".
 *
 * Fail-closed: an unclassified package belongs to NO context, so it never silently
 * routes into the wrong database (the aero:verify-tiers gate refuses install if any
 * package is unclassified).
 */
final class PackageTier
{
    public const PLATFORM = 'platform';

    public const CORE = 'core';

    public const SHARABLE = 'sharable';

    public const PRODUCT = 'product';

    public const TIERS = [self::PLATFORM, self::CORE, self::SHARABLE, self::PRODUCT];

    /** Install context => the tiers whose migrations run in it. */
    public const CONTEXT_TIERS = [
        'central' => [self::PLATFORM, self::SHARABLE],
        'tenant' => [self::CORE, self::SHARABLE, self::PRODUCT],
        'standalone' => [self::CORE, self::SHARABLE, self::PRODUCT],
    ];

    /**
     * The tier of a package by its short name (e.g. 'hrmac', 'platform', 'hrm'),
     * read from packages/aero-{name}/composer.json (source of truth) or vendor/aero/{name}.
     * Null when unknown/unclassified.
     */
    public static function tierOf(string $packageShortName): ?string
    {
        foreach ([
            base_path("packages/aero-{$packageShortName}/composer.json"),
            base_path("vendor/aero/{$packageShortName}/composer.json"),
        ] as $path) {
            if (is_file($path)) {
                $data = json_decode((string) file_get_contents($path), true);
                $tier = is_array($data) ? ($data['extra']['aero']['tier'] ?? null) : null;

                return is_string($tier) && in_array($tier, self::TIERS, true) ? $tier : null;
            }
        }

        return null;
    }

    /**
     * Does this package's tier belong in the given install context
     * ('central' | 'tenant' | 'standalone')? Fail-closed on unknown tier/context.
     */
    public static function belongsIn(string $packageShortName, string $context): bool
    {
        $tier = self::tierOf($packageShortName);
        if ($tier === null) {
            return false;
        }

        return in_array($tier, self::CONTEXT_TIERS[$context] ?? [], true);
    }

    /**
     * Absolute, real migration-directory paths of every installed package whose tier
     * belongs in $context. Scans packages/ (source of truth) and vendor/aero. Used by the
     * platform landlord-migrator override and the context-aware loader to confine `migrate`
     * to the correct set. Does NOT apply product subscription/purchase gating — the caller
     * layers that on top for tenant/standalone.
     *
     * @return array<int, string>
     */
    public static function migrationPathsForContext(string $context): array
    {
        $allowed = self::CONTEXT_TIERS[$context] ?? [];

        // De-dupe by package short-name; packages/ (second glob) wins over vendor/aero.
        $byName = [];
        foreach ([base_path('vendor/aero/*'), base_path('packages/aero-*')] as $glob) {
            foreach ((array) glob($glob, GLOB_ONLYDIR) as $dir) {
                $byName[preg_replace('/^aero-/', '', basename($dir))] = $dir;
            }
        }

        $paths = [];
        foreach ($byName as $name => $dir) {
            if (! in_array(self::tierOf($name), $allowed, true)) {
                continue;
            }
            $mig = $dir.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
            if (is_dir($mig) && ($real = realpath($mig)) !== false) {
                $paths[] = $real;
            }
        }

        return array_values(array_unique($paths));
    }
}
