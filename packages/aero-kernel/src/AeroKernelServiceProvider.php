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
