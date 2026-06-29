<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\File;

/**
 * Resolves the migration paths that should run against a TENANT database.
 *
 * A tenant DB gets: the baseline framework packages every tenant needs (core, auth,
 * ui, i18n, notifications, hrmac — from config('hrmac.baseline_modules')) PLUS the
 * tenant's subscribed product modules (with dependencies). It explicitly does NOT run
 * the full migration set — central-only tables (tenants, plans, subscriptions,
 * landlord_users, domains, …) must never be created in a tenant DB.
 *
 * Shared by ProvisionTenant (tenant creation) and the tenant:migrate command so both
 * use the SAME curated set (previously they diverged: the command ran the full migrate
 * and polluted tenant DBs / hit cross-DB FK clashes).
 */
class TenantMigrationPaths
{
    /** Product modules that are not separate migration packages. */
    private const EXCLUDED_MODULES = ['core', 'dashboard'];

    /** Product module -> required product modules. Core/baseline added separately. */
    private const MODULE_DEPENDENCIES = [
        'hrm' => [],
        'finance' => [],
        'crm' => [],
        'project' => [],
        'ims' => [],
        'pos' => ['ims'],
        'scm' => ['ims'],
        'quality' => [],
        'dms' => [],
        'compliance' => [],
        'assistant' => [],
        'rfi' => [],
    ];

    /**
     * @return array<int, string> Migration paths (relative to base_path, plus an
     *                            absolute app tenant path if present).
     */
    public function forTenant(Tenant $tenant): array
    {
        $paths = [];

        // Baseline framework packages every tenant DB needs (incl. core).
        $baseline = (array) config('hrmac.baseline_modules', ['core', 'auth', 'ui', 'i18n', 'notifications', 'hrmac']);
        foreach ($baseline as $pkg) {
            if ($p = $this->resolvePackagePath($pkg)) {
                $paths[] = $p;
            }
        }

        // Tenant's subscribed product modules (+ dependencies). Canonical source is
        // product_subscriptions via getSubscribedProductModules (baseline entries are
        // filtered out below), retiring the deprecated tenant_module pivot read.
        $modules = $this->resolveModuleDependencies($tenant->subscribed_product_modules);
        foreach ($modules as $code) {
            if (in_array($code, self::EXCLUDED_MODULES, true) || in_array($code, $baseline, true)) {
                continue;
            }
            if ($p = $this->resolvePackagePath($code)) {
                $paths[] = $p;
            }
        }

        // App-level tenant-only migrations, if the host defines any.
        $appTenant = database_path('migrations/tenant');
        if (File::exists($appTenant)) {
            $paths[] = $appTenant;
        }

        return array_values(array_unique($paths));
    }

    /**
     * Resolve a package's migrations dir (vendor first, then packages/ for dev).
     */
    private function resolvePackagePath(string $pkg): ?string
    {
        foreach (["vendor/aero/{$pkg}/database/migrations", "packages/aero-{$pkg}/database/migrations"] as $p) {
            if (File::exists(base_path($p))) {
                return $p;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $moduleCodes
     * @return array<int, string>
     */
    public function resolveModuleDependencies(array $moduleCodes): array
    {
        $resolved = [];
        $toProcess = $moduleCodes;

        while (! empty($toProcess)) {
            $current = array_shift($toProcess);
            if (in_array($current, $resolved, true)) {
                continue;
            }
            $resolved[] = $current;
            foreach (self::MODULE_DEPENDENCIES[$current] ?? [] as $dep) {
                if (! in_array($dep, $resolved, true) && ! in_array($dep, $toProcess, true)) {
                    $toProcess[] = $dep;
                }
            }
        }

        return array_values(array_unique($resolved));
    }
}
