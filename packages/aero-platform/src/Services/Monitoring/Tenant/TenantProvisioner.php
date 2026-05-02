<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Monitoring\Tenant;

use Aero\Platform\Models\Module;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Arr;
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

        $trialEndsAt = now()->addDays((int) config('platform.trial_days', 14));
        $planId = $plan['plan_id'] ?? $this->resolvePlanId($plan['plan_slug'] ?? null);
        $modules = $this->sanitizeModulesAgainstPlan($planId, $this->cleanModules($plan['modules'] ?? []));

        $email = (string) Arr::get($details, 'email');
        $subdomain = (string) Arr::get($details, 'subdomain');

        // Check if tenant already exists from verification step
        // This preserves admin_email_verified_at and admin_phone_verified_at
        // Fix: Use AND (not OR) to prevent tenant takeover by matching only one field
        $existingTenant = Tenant::where('email', $email)
            ->where('subdomain', $subdomain)
            ->whereIn('status', [Tenant::STATUS_PENDING, Tenant::STATUS_FAILED])
            ->first();

        if ($existingTenant) {
            // Update existing tenant with full registration data
            // Preserve verification timestamps that were set during email/phone verification
            $existingTenant->update([
                'name' => (string) Arr::get($details, 'name'),
                'type' => (string) Arr::get($account, 'type', 'company'),
                'subdomain' => $subdomain,
                'email' => $email,
                'phone' => Arr::get($details, 'phone'),
                'plan_id' => $planId,
                'subscription_plan' => Arr::get($plan, 'billing_cycle'),
                'modules' => $modules,
                'trial_ends_at' => $trialEndsAt,
                'subscription_ends_at' => null,
                'status' => Tenant::STATUS_PENDING,
                'provisioning_step' => null,
                'admin_data' => null,
                'maintenance_mode' => false,
                'data' => [
                    'owner_name' => Arr::get($details, 'owner_name'),
                    'owner_email' => Arr::get($details, 'owner_email', $email),
                    'owner_phone' => Arr::get($details, 'owner_phone'),
                    'team_size' => Arr::get($details, 'team_size'),
                    'industry' => Arr::get($details, 'industry'),
                    'notes' => Arr::get($plan, 'notes'),
                    'registration_ip' => request()->ip(),
                    'registered_at' => now()->toIso8601String(),
                ],
            ]);

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
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => (string) Arr::get($details, 'name'),
            'type' => (string) Arr::get($account, 'type', 'company'),
            'subdomain' => $subdomain,
            'email' => $email,
            'phone' => Arr::get($details, 'phone'),
            'plan_id' => $planId,
            'subscription_plan' => Arr::get($plan, 'billing_cycle'),
            'modules' => $modules,
            'trial_ends_at' => $trialEndsAt,
            'subscription_ends_at' => null,
            'status' => Tenant::STATUS_PENDING,
            'provisioning_step' => null,
            'admin_data' => null,
            'maintenance_mode' => false,
            'data' => [
                'owner_name' => Arr::get($details, 'owner_name'),
                'owner_email' => Arr::get($details, 'owner_email', $email),
                'owner_phone' => Arr::get($details, 'owner_phone'),
                'team_size' => Arr::get($details, 'team_size'),
                'industry' => Arr::get($details, 'industry'),
                'notes' => Arr::get($plan, 'notes'),
                'registration_ip' => request()->ip(),
                'registered_at' => now()->toIso8601String(),
            ],
        ]);

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

        $trialEndsAt = now()->addDays((int) config('platform.trial_days', 14));
        $planId = $plan['plan_id'] ?? $this->resolvePlanId($plan['plan_slug'] ?? null);
        $modules = $this->sanitizeModulesAgainstPlan($planId, $this->cleanModules($plan['modules'] ?? []));

        // Get existing data as array (handles ArrayObject or array)
        $existingData = $tenant->data instanceof \ArrayObject
            ? $tenant->data->getArrayCopy()
            : (array) ($tenant->data ?? []);

        // Update tenant with full registration data
        // Preserve company verification timestamps
        $tenant->update([
            'name' => (string) Arr::get($details, 'name', $tenant->name),
            'type' => (string) Arr::get($account, 'type', $tenant->type ?? 'company'),
            'plan_id' => $planId ?? $tenant->plan_id,
            'subscription_plan' => Arr::get($plan, 'billing_cycle', $tenant->subscription_plan),
            'modules' => ! empty($modules) ? $modules : $tenant->modules,
            'trial_ends_at' => $trialEndsAt,
            'subscription_ends_at' => null,
            'status' => Tenant::STATUS_PENDING,
            'provisioning_step' => null,
            'maintenance_mode' => false,
            'data' => array_merge($existingData, [
                'owner_name' => Arr::get($details, 'owner_name'),
                'owner_email' => Arr::get($details, 'owner_email', $tenant->email),
                'owner_phone' => Arr::get($details, 'owner_phone'),
                'team_size' => Arr::get($details, 'team_size'),
                'industry' => Arr::get($details, 'industry'),
                'notes' => Arr::get($plan, 'notes'),
                'registration_ip' => request()->ip(),
                'registered_at' => now()->toIso8601String(),
            ]),
        ]);

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

        // Since plans no longer include modules, validate against all active modules
        $allowed = Module::where('is_active', true)->pluck('code')->all();
        $allowed = array_values(array_filter($allowed));

        // Validate that at least one module exists
        if (empty($allowed)) {
            Log::warning('No active modules found in system');
            throw new \InvalidArgumentException(
                "No active modules found in the system. Please contact support."
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
                "The following modules are not available: ".implode(', ', $invalid)
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

        $moduleIds = Module::whereIn('code', $moduleCodes)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $syncData = array_fill_keys($moduleIds, [
            'is_active' => true,
            'subscribed_at' => now(),
        ]);

        $tenant->modules()->sync($syncData);
    }

    private function buildDomain(?string $subdomain): string
    {
        $baseDomain = config('platform.central_domain', 'localhost');
        $cleanSubdomain = Str::slug((string) $subdomain);

        return sprintf('%s.%s', $cleanSubdomain, $baseDomain);
    }
}
