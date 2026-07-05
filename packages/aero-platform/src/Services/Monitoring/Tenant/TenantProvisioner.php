<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Monitoring\Tenant;

use Aero\Platform\Models\Module;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * TenantProvisioner Service
 *
 * Handles the creation and provisioning of new tenants.
 *
 * The provisioning flow (async):
 * 1. Validate and prepare tenant data
 * 2. Create Tenant record in central database with status 'pending'
 * 3. Store admin credentials in admin_data column (hashed password)
 * 4. Create Domain record for the tenant
 * 5. Dispatch ProvisionTenant job which:
 *    - Creates the tenant database
 *    - Runs migrations
 *    - Seeds the admin user
 *    - Activates the tenant
 */
class TenantProvisioner
{
    /**
     * Create a new tenant from registration payload.
     *
     * This creates the Tenant and Domain records but does NOT trigger
     * database provisioning. Call dispatchProvisioning() after this
     * to start the async provisioning process.
     *
     * If a tenant already exists from the verification step, it will be
     * updated with the full registration data while preserving verification
     * timestamps (admin_email_verified_at, admin_phone_verified_at).
     *
     * @param  array  $payload  Registration data from multi-step wizard
     */
    public function createFromRegistration(array $payload): Tenant
    {
        $account = $payload['account'] ?? [];
        $details = $payload['details'] ?? [];
        $plan = $payload['plan'] ?? [];
        $trial = $payload['trial'] ?? [];

        $planId = $plan['plan_id'] ?? $this->resolvePlanId($plan['plan_slug'] ?? null);
        $modules = $this->sanitizeModulesAgainstPlan($planId, $this->cleanModules($plan['modules'] ?? []));

        $email = (string) Arr::get($details, 'email');
        $subdomain = (string) Arr::get($details, 'subdomain');

        // BYOC: if tenant opted in, assemble encrypted credential fields
        $byoc = $payload['byoc'] ?? [];
        $byocData = [];
        if (! empty($byoc['enabled'])) {
            $byocData = [
                'byoc_enabled' => true,
                'byoc_db_driver' => $byoc['db_driver'] ?? 'mysql',
                'byoc_db_host' => $byoc['db_host'] ?? null,
                'byoc_db_port' => $byoc['db_port'] ?? 3306,
                'byoc_db_name' => $byoc['db_name'] ?? null,
                'byoc_db_username' => encrypt($byoc['db_username'] ?? ''),
                'byoc_db_password' => encrypt($byoc['db_password'] ?? ''),
                'byoc_db_ssl_mode' => $byoc['db_ssl_mode'] ?? null,
            ];
        }

        // Look for an existing pending/failed tenant to reuse.
        // Order of preference: exact match (email + subdomain), then subdomain,
        // then email. This prevents duplicate-key violations when a tenant was
        // created in a previous request but the session was lost.
        $pendingStatuses = [Tenant::STATUS_PENDING, Tenant::STATUS_FAILED];
        $existingTenant = Tenant::where('email', $email)
            ->where('subdomain', $subdomain)
            ->whereIn('status', $pendingStatuses)
            ->first();

        if (! $existingTenant) {
            $existingTenant = Tenant::where('subdomain', $subdomain)
                ->whereIn('status', $pendingStatuses)
                ->first();
        }

        if (! $existingTenant) {
            $existingTenant = Tenant::where('email', $email)
                ->whereIn('status', $pendingStatuses)
                ->first();
        }

        if ($existingTenant) {
            // Update existing tenant with full registration data
            // Preserve verification timestamps that were set during email/phone verification
            $existingTenant->update(array_merge([
                'name' => (string) Arr::get($details, 'name'),
                'type' => (string) Arr::get($account, 'type', 'company'),
                'subdomain' => $subdomain,
                'email' => $email,
                'phone' => Arr::get($details, 'phone'),
                'status' => Tenant::STATUS_PENDING,
                'provisioning_step' => null,
                'admin_data' => null,
                'maintenance_mode' => false,
                // VirtualColumn attributes — set top-level so they encode into `data`
                // (nesting under a literal `data` key is dropped by the re-encode).
                'owner_name' => Arr::get($details, 'owner_name'),
                'owner_email' => Arr::get($details, 'owner_email', $email),
                'owner_phone' => Arr::get($details, 'owner_phone'),
                'team_size' => Arr::get($details, 'team_size'),
                'industry' => Arr::get($details, 'industry'),
                'plan_id' => $planId,
                'billing_cycle' => Arr::get($plan, 'billing_cycle'),
                'selected_modules' => $modules,
                'notes' => Arr::get($plan, 'notes'),
                'registration_ip' => request()->ip(),
                'registered_at' => now()->toIso8601String(),
            ], $byocData));

            // Create domain if doesn't exist
            if ($existingTenant->domains()->count() === 0) {
                $existingTenant->domains()->create([
                    'domain' => $this->buildDomain($subdomain),
                    'is_primary' => true,
                ]);
            }

            // Sync tenant_module pivot records for provisioning
            $this->syncTenantModules($existingTenant, $modules);

            return $existingTenant->fresh();
        }

        // Create new tenant if none exists
        $tenant = Tenant::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => (string) Arr::get($details, 'name'),
            'type' => (string) Arr::get($account, 'type', 'company'),
            'subdomain' => $subdomain,
            'email' => $email,
            'phone' => Arr::get($details, 'phone'),
            'status' => Tenant::STATUS_PENDING,
            'provisioning_step' => null,
            'admin_data' => null,
            'maintenance_mode' => false,
            // VirtualColumn attributes — set top-level so they encode into `data`
            // (nesting under a literal `data` key is dropped by the re-encode).
            'owner_name' => Arr::get($details, 'owner_name'),
            'owner_email' => Arr::get($details, 'owner_email', $email),
            'owner_phone' => Arr::get($details, 'owner_phone'),
            'team_size' => Arr::get($details, 'team_size'),
            'industry' => Arr::get($details, 'industry'),
            'plan_id' => $planId,
            'billing_cycle' => Arr::get($plan, 'billing_cycle'),
            'selected_modules' => $modules,
            'notes' => Arr::get($plan, 'notes'),
            'registration_ip' => request()->ip(),
            'registered_at' => now()->toIso8601String(),
        ], $byocData));

        // Create the primary domain for tenant routing
        $tenant->domains()->create([
            'domain' => $this->buildDomain($subdomain),
            'is_primary' => true,
        ]);

        // Sync tenant_module pivot records for provisioning
        $this->syncTenantModules($tenant, $modules);

        return $tenant;
    }

    /**
     * Update an existing tenant from registration payload.
     *
     * Used when resuming an incomplete registration for a tenant
     * that was created during the verification step.
     *
     * @param  Tenant  $tenant  Existing tenant to update
     * @param  array  $payload  Registration data from multi-step wizard
     */
    public function updateFromRegistration(Tenant $tenant, array $payload): Tenant
    {
        $account = $payload['account'] ?? [];
        $details = $payload['details'] ?? [];
        $plan = $payload['plan'] ?? [];

        $planId = $plan['plan_id'] ?? $this->resolvePlanId($plan['plan_slug'] ?? null);
        $modules = $this->sanitizeModulesAgainstPlan($planId, $this->cleanModules($plan['modules'] ?? []));

        // BYOC: if tenant opted in, assemble encrypted credential fields
        $byoc = $payload['byoc'] ?? [];
        $byocData = [];
        if (! empty($byoc['enabled'])) {
            $byocData = [
                'byoc_enabled' => true,
                'byoc_db_driver' => $byoc['db_driver'] ?? 'mysql',
                'byoc_db_host' => $byoc['db_host'] ?? null,
                'byoc_db_port' => $byoc['db_port'] ?? 3306,
                'byoc_db_name' => $byoc['db_name'] ?? null,
                'byoc_db_username' => encrypt($byoc['db_username'] ?? ''),
                'byoc_db_password' => encrypt($byoc['db_password'] ?? ''),
                'byoc_db_ssl_mode' => $byoc['db_ssl_mode'] ?? null,
            ];
        }

        // Update tenant with full registration data.
        // owner_*, plan_id, selected_modules, … are stancl VirtualColumn attributes:
        // they MUST be assigned as TOP-LEVEL keys so the VirtualColumn encoder writes
        // them into the `data` JSON. Nesting them under a literal `data` key did NOT
        // survive the re-encode on save (the encoder rebuilds `data` from the model's
        // top-level attributes), so these fields were silently lost. Existing `data`
        // keys (currency, verification timestamps, …) are preserved automatically
        // because they are already loaded as attributes on the model.
        $tenant->update(array_merge([
            'name' => (string) Arr::get($details, 'name', $tenant->name),
            'type' => (string) Arr::get($account, 'type', $tenant->type ?? 'company'),
            'status' => Tenant::STATUS_PENDING,
            'provisioning_step' => null,
            'maintenance_mode' => false,
            'owner_name' => Arr::get($details, 'owner_name'),
            'owner_email' => Arr::get($details, 'owner_email', $tenant->email),
            'owner_phone' => Arr::get($details, 'owner_phone'),
            'team_size' => Arr::get($details, 'team_size'),
            'industry' => Arr::get($details, 'industry'),
            'plan_id' => $planId,
            'billing_cycle' => Arr::get($plan, 'billing_cycle'),
            'selected_modules' => $modules,
            'notes' => Arr::get($plan, 'notes'),
            'registration_ip' => request()->ip(),
            'registered_at' => now()->toIso8601String(),
        ], $byocData));

        // Create domain if doesn't exist
        if ($tenant->domains()->count() === 0) {
            $tenant->domains()->create([
                'domain' => $this->buildDomain($tenant->subdomain),
                'is_primary' => true,
            ]);
        }

        // Sync tenant_module pivot records for provisioning
        $this->syncTenantModules($tenant, $modules);

        return $tenant->fresh();
    }

    /**
     * Resolve plan UUID from slug.
     */
    private function resolvePlanId(?string $planSlug): ?string
    {
        if (! $planSlug) {
            return null;
        }

        return Plan::where('slug', $planSlug)->value('id');
    }

    private function cleanModules(array $modules): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($module) => $module !== null
                ? Str::slug((string) $module, '_')
                : null,
            $modules
        ))));
    }

    private function sanitizeModulesAgainstPlan(?string $planId, array $modules): array
    {
        if (! $planId) {
            return $modules;
        }

        // Product modules are authoritative in module_pricing; core is always available
        $productModules = DB::table('module_pricing')
            ->where('is_active', true)
            ->pluck('module_code')
            ->all();
        $allowed = array_values(array_unique(array_merge(['core'], array_filter($productModules))));

        // Validate that at least one sellable product exists
        if (empty($productModules)) {
            Log::warning('No active products found in module_pricing table');
            throw new \InvalidArgumentException(
                'No active products found in the system. Please contact support.'
            );
        }

        // If no modules requested, default to core module
        if (empty($modules)) {
            $modules = ['core'];
        }

        // Validate requested modules against available modules
        $invalid = array_diff($modules, $allowed);
        if (! empty($invalid)) {
            Log::warning('Requested modules not available', [
                'plan_id' => $planId,
                'requested' => $modules,
                'allowed' => $allowed,
                'invalid' => $invalid,
            ]);
            throw new \InvalidArgumentException(
                'The following modules are not available: '.implode(', ', $invalid)
            );
        }

        return $modules;
    }

    /**
     * Sync tenant_module pivot records for the given module codes.
     */
    private function syncTenantModules(Tenant $tenant, array $moduleCodes): void
    {
        $moduleCodes = array_values(array_unique(array_filter($moduleCodes)));
        if (empty($moduleCodes)) {
            return;
        }

        // The HRMAC Module model + tenant_module pivot live on the CENTRAL/landlord DB.
        // This runs during signup trial-activation — a platform/central request with no
        // tenant context resolved — so HrmacModel's tenant-context guard would throw
        // ("queried outside of a valid HRMAC context"). Central landlord work is
        // legitimate (no tenant to leak between), so run it with the guard disabled.
        \Aero\Contracts\AeroMode::withoutTenantContextGuard(function () use ($tenant, $moduleCodes) {
            $moduleIds = Module::whereIn('code', $moduleCodes)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            $syncData = array_fill_keys($moduleIds, [
                'is_active' => true,
                'subscribed_at' => now(),
            ]);

            $tenant->modules()->sync($syncData);
        });
    }

    private function buildDomain(?string $subdomain): string
    {
        $baseDomain = config('platform.central_domain', 'localhost');
        $cleanSubdomain = Str::slug((string) $subdomain);

        return sprintf('%s.%s', $cleanSubdomain, $baseDomain);
    }
}
