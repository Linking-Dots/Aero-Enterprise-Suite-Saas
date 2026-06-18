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
 *
 * Where this is enforced at runtime (so even a raw `php artisan migrate` is tier-safe):
 *   - SaaS central : AeroPlatformServiceProvider::overrideMigratorForLandlord() confines the
 *                    migrator to migrationPathsForContext('central').
 *   - SaaS tenant  : the same override excludes platform-tier migrations on a tenant DB.
 *   - standalone   : needs NO runtime override — the host physically installs exactly its tier
 *                    set (core+sharable+product, never platform), so the migrator's global
 *                    loadMigrationsFrom set already equals the standalone tier. Tier-correct by
 *                    construction; the install wizard (MigrationStep) is the gate at install time.
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
     * Scan every installed aero package (packages/ source-of-truth + vendor/aero) and
     * classify by tier, collecting unclassified/invalid packages as errors. Pure data —
     * no console I/O — so BOTH the `aero:verify-tiers` command AND the install-time
     * MigrationStep can gate on it. MigrationStep runs in a WEB request during the
     * wizard, where console commands aren't registered (AeroCoreServiceProvider only
     * registers commands when runningInConsole()); calling this directly avoids that
     * coupling. Stub dirs without composer.json are recorded as skipped, not fatal.
     *
     * @return array{ok:bool, by_tier:array<string,int>, errors:array<string,string>, skipped:array<int,string>}
     */
    public static function verifyAll(): array
    {
        $byTier = [self::PLATFORM => [], self::CORE => [], self::SHARABLE => [], self::PRODUCT => []];
        $errors = [];
        $skipped = [];

        // vendor/aero first, then packages/ (source of truth). Basenames differ
        // ('core' vs 'aero-core'), so both copies are scanned — harmless for a
        // "does every package declare a valid tier?" gate.
        $dirs = [];
        foreach ([base_path('vendor/aero/*'), base_path('packages/aero-*')] as $glob) {
            foreach ((array) glob($glob, GLOB_ONLYDIR) as $dir) {
                $dirs[basename($dir)] = $dir;
            }
        }
        ksort($dirs);

        foreach ($dirs as $name => $dir) {
            $composer = $dir.DIRECTORY_SEPARATOR.'composer.json';
            if (! is_file($composer)) {
                $skipped[] = $name;
                continue;
            }

            $data = json_decode((string) file_get_contents($composer), true);
            if (! is_array($data)) {
                $errors[$name] = 'composer.json is not valid JSON';
                continue;
            }

            $tier = $data['extra']['aero']['tier'] ?? null;
            if ($tier === null) {
                $errors[$name] = 'missing extra.aero.tier';
                continue;
            }
            if (! in_array($tier, self::TIERS, true)) {
                $errors[$name] = "invalid tier '{$tier}' (must be one of: ".implode('|', self::TIERS).')';
                continue;
            }

            $byTier[$tier][] = $name;
        }

        sort($skipped);

        return [
            'ok' => $errors === [],
            'by_tier' => array_map('count', $byTier),
            'errors' => $errors,
            'skipped' => $skipped,
        ];
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
        return self::migrationPathsForTiers(self::CONTEXT_TIERS[$context] ?? []);
    }

    /**
     * Absolute, real migration-directory paths of every installed package whose tier is in
     * $tiers. Same scan/de-dupe as migrationPathsForContext but with an explicit tier set —
     * lets callers take only PART of a context (e.g. tenant's UNCONDITIONAL core+sharable,
     * leaving products to be subscription-gated one package at a time).
     *
     * @param  array<int, string>  $tiers
     * @return array<int, string>
     */
    public static function migrationPathsForTiers(array $tiers): array
    {
        // De-dupe by package short-name; packages/ (second glob) wins over vendor/aero.
        $byName = [];
        foreach ([base_path('vendor/aero/*'), base_path('packages/aero-*')] as $glob) {
            foreach ((array) glob($glob, GLOB_ONLYDIR) as $dir) {
                $byName[preg_replace('/^aero-/', '', basename($dir))] = $dir;
            }
        }

        $paths = [];
        foreach ($byName as $name => $dir) {
            if (! in_array(self::tierOf($name), $tiers, true)) {
                continue;
            }
            if (($real = self::migrationDirOf($dir)) !== null) {
                $paths[] = $real;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * The real migration-directory path of a SINGLE package by short name (e.g. 'hrm'),
     * preferring packages/ over vendor/aero. Null when the package or its migrations dir is
     * absent. Used to resolve subscription-gated product packages one at a time without
     * re-implementing the vendor/packages fallback ladder at the call site.
     */
    public static function migrationPathForPackage(string $packageShortName): ?string
    {
        foreach ([
            base_path("packages/aero-{$packageShortName}"),
            base_path("vendor/aero/{$packageShortName}"),
        ] as $dir) {
            if (is_dir($dir) && ($real = self::migrationDirOf($dir)) !== null) {
                return $real;
            }
        }

        return null;
    }

    /** realpath of {$packageDir}/database/migrations, or null if it isn't a dir. */
    private static function migrationDirOf(string $packageDir): ?string
    {
        $mig = $packageDir.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        if (is_dir($mig) && ($real = realpath($mig)) !== false) {
            return $real;
        }

        return null;
    }
}
