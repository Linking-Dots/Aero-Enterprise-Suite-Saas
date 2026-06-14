<?php

declare(strict_types=1);

namespace Aero\Kernel;

use Illuminate\Support\ServiceProvider;

/**
 * Aero Kernel — shared runtime foundation consumed by core and platform.
 *
 * Dependency decoupling (Phase 3): symbols that both aero-core (tenant/standalone)
 * and aero-platform (central/SaaS) need at runtime — audit event taxonomy, the
 * module registry, the license signing core — are relocated here out of aero-core so
 * neither sibling has to depend on the other. The kernel itself depends only on
 * aero-contracts + aero-infrastructure.
 */
class AeroKernelServiceProvider extends ServiceProvider
{
    /**
     * Backward-compatibility class aliases: legacy aero-core FQN => canonical kernel
     * FQN. Registered in register() (before any consumer uses the symbol) so the broad
     * fan-out of existing `Aero\Core\...` references keeps resolving with zero edits
     * while they are repointed incrementally. Removed in the final enforcement phase.
     */
    private const LEGACY_ALIASES = [
        \Aero\Kernel\Audit\AuditEventType::class   => 'Aero\\Core\\Services\\Audit\\AuditEventType',
        \Aero\Kernel\Support\SafeRedirect::class   => 'Aero\\Core\\Support\\SafeRedirect',
        \Aero\Kernel\Support\TenantCache::class    => 'Aero\\Core\\Support\\TenantCache',
        \Aero\Kernel\Module\ModuleRegistry::class  => 'Aero\\Core\\Services\\ModuleRegistry',
        \Aero\Kernel\Module\ModuleDiscoveryService::class => 'Aero\\Core\\Services\\Module\\ModuleDiscoveryService',
        \Aero\Kernel\Services\UserRelationshipRegistry::class => 'Aero\\Core\\Services\\UserRelationshipRegistry',
        // NOTE: the base Http Controller is NOT aliased — it is a base class that gets
        // *extended*, and pure-unit tests reflect on subclasses without booting providers
        // (so a runtime alias never fires). It uses a thin BC shim class instead
        // (Aero\Core\Http\Controllers\Controller extends the kernel base), mirroring the
        // CentralModel/TenantModel shim pattern.
    ];

    public function register(): void
    {
        foreach (self::LEGACY_ALIASES as $canonical => $legacy) {
            if (! class_exists($legacy, false)) {
                class_alias($canonical, $legacy);
            }
        }
    }

    public function boot(): void
    {
        //
    }
}
