<?php

declare(strict_types=1);

namespace Aero\Core\Services\Module;

use Aero\Core\Models\ModuleLicense;
use Aero\HRMAC\Models\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Module Entitlement — the SINGLE source of truth for "which module codes is
 * this instance entitled to right now?".
 *
 * Both entitlement paths feed this one resolver so the nav filter, access
 * checks, and provisioning all read from the same place in every context:
 *   • SaaS       → the tenant's active product subscriptions (product.module_code)
 *   • Standalone → active module licenses (module_licenses)
 *   • Always     → core/platform + the hrmac baseline foundation + is_core modules
 *
 * Returns an array of entitled module codes, or NULL meaning "unrestricted":
 *   • no tenant resolved yet (SaaS, pre-tenancy),
 *   • a standalone install with NO licensing configured at all (dev/unlicensed),
 *   • any failure (fail-open — never hide the whole app on an error).
 */
class ModuleEntitlementService
{
    /** Sentinel stored in cache to represent the "unrestricted" (null) result. */
    private const UNRESTRICTED = '__ALL__';

    /**
     * @return array<int, string>|null  entitled module codes, or null = unrestricted
     */
    public function entitledModuleCodes(): ?array
    {
        $tenantId = (function_exists('tenant') && tenant()) ? tenant()->getTenantKey() : 'none';

        // Runs on every authenticated page load — cache per tenant. Invalidated
        // by ProductSubscriptionObserver (SaaS) / license changes (standalone).
        $cached = Cache::remember("module_entitlement:{$tenantId}", 600, function () {
            return $this->compute() ?? [self::UNRESTRICTED];
        });

        return $cached === [self::UNRESTRICTED] ? null : $cached;
    }

    /** @return array<int, string>|null */
    private function compute(): ?array
    {
        try {
            $mode = function_exists('aero_mode') ? aero_mode() : 'saas';
            $codes = $this->foundation();

            if ($mode === 'saas') {
                $tenant = (function_exists('tenant') && tenant()) ? tenant() : null;
                if (! $tenant) {
                    return null; // pre-tenancy — do not over-filter
                }

                return $this->clean(array_merge(
                    $codes,
                    (array) ($tenant->subscribed_product_modules ?? []),
                    $this->overrideCodes($tenant->getTenantKey()),
                ));
            }

            // Standalone: entitlement comes from active module licenses. When the
            // license system holds no records at all (unconfigured install), treat
            // as unrestricted rather than hiding every purchasable module.
            $licensed = $this->licensedModuleCodes();
            if ($licensed === null) {
                return null;
            }

            return $this->clean(array_merge($codes, $licensed, $this->overrideCodes(null)));
        } catch (Throwable $e) {
            Log::warning('ModuleEntitlementService: resolution failed, treating as unrestricted', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** Foundation modules entitled in every context. */
    private function foundation(): array
    {
        $codes = ['core', 'platform'];
        $codes = array_merge($codes, (array) config('hrmac.baseline_modules', []));

        if (class_exists(Module::class)) {
            $codes = array_merge(
                $codes,
                Module::query()->where('is_core', true)->where('is_active', true)->pluck('code')->all()
            );
        }

        return $codes;
    }

    /**
     * Active-licensed module codes (standalone). Returns NULL when no licenses
     * exist at all so the caller falls back to unrestricted.
     *
     * @return array<int, string>|null
     */
    private function licensedModuleCodes(): ?array
    {
        if (! class_exists(ModuleLicense::class)) {
            return null;
        }

        if (ModuleLicense::query()->count() === 0) {
            return null; // no licensing configured → unrestricted (dev/unlicensed)
        }

        return ModuleLicense::query()->active()->pluck('module_code')->filter()->values()->all();
    }

    /**
     * Open override grants for this scope — modules an admin comped / trialed /
     * grandfathered outside a purchase (tenant_entitlements, source=override).
     *
     * Read via the query builder (not the platform TenantEntitlement model) so
     * aero-core keeps no code edge to aero-platform. Guarded on table existence
     * and fail-open: a standalone install without the ledger table simply gets
     * no overrides rather than an error.
     *
     * @return array<int, string>
     */
    private function overrideCodes(?string $tenantId): array
    {
        try {
            if (! Schema::hasTable('tenant_entitlements')) {
                return [];
            }

            return DB::table('tenant_entitlements')
                ->where('source', 'override')
                ->whereNull('revoked_at')
                ->when(
                    $tenantId === null,
                    fn ($q) => $q->whereNull('tenant_id'),
                    fn ($q) => $q->where('tenant_id', $tenantId),
                )
                ->pluck('module_code')
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    /** @return array<int, string> */
    private function clean(array $codes): array
    {
        return array_values(array_unique(array_filter($codes)));
    }

    /** Invalidate the cached entitlement for a tenant (or the standalone/'none' scope). */
    public function forget(?string $tenantId = null): void
    {
        Cache::forget('module_entitlement:'.($tenantId ?? 'none'));
    }
}
