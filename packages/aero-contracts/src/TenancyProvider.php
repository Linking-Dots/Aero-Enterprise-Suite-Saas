<?php

declare(strict_types=1);

namespace Aero\Contracts;

/**
 * Inversion seam for mode-dependent tenancy behaviour that aero-core CONSUMES
 * but aero-platform PROVIDES.
 *
 * Direction of control is inverted: aero-core depends only on this contract and
 * resolves it from the container; aero-platform binds a concrete implementation
 * at boot and registers it into core's extension points. In standalone mode
 * (platform absent) aero-core falls back to a null-object implementation, so core
 * never names a platform class — satisfying strict core ⟂ platform sibling
 * isolation (dependency-architecture.md rules 9–10).
 *
 * This contract is the replacement target for the hidden core→platform coupling
 * inventoried in docs/plans/dependency-decoupling-phase0-audit.md §5
 * (the `class_exists('Aero\Platform\…')` string guards in core for domain
 * resolution, onboarding gating, tenant-active enforcement, etc.).
 *
 * Phase 1 declares the seam only (interface). The platform implementation and the
 * core consumption/binding land in Phase 5 (V1 final + V4); method shapes may be
 * refined there as each guard is migrated.
 */
interface TenancyProvider
{
    /**
     * Is a platform-backed tenancy layer active for this request/process?
     *
     * True only in SaaS mode with aero-platform installed and a tenant context
     * resolvable. Standalone always returns false. Replaces the
     * `class_exists('Aero\Platform\AeroPlatformServiceProvider')` mode guards.
     */
    public function isActive(): bool;

    /**
     * Resolve the tenant/domain record for an incoming HTTP host, or null when the
     * host maps to no tenant (central/admin/standalone). Replaces
     * ParsesHostDomain's `class_exists('Aero\Platform\Models\Domain')` lookup.
     *
     * The concrete record shape is owned by the platform implementation; callers
     * in core must treat it opaquely (read via accessors, never type-hint a
     * platform class).
     */
    public function resolveDomain(string $host): ?object;

    /**
     * Has the current tenant completed onboarding? Standalone returns true.
     * Replaces EnsureTenantIsSetup's
     * `Aero\Platform\Http\Controllers\TenantOnboardingController::isOnboardingCompleted()`.
     */
    public function isOnboardingCompleted(): bool;

    /**
     * Middleware aliases that platform contributes to the web/api groups when the
     * tenancy layer is active (e.g. tenant-active enforcement). Returns an empty
     * array in standalone. Replaces core pushing
     * `Aero\Platform\Http\Middleware\EnsureTenantIsActive` by string.
     *
     * @return array<int, string>
     */
    public function middlewareForActiveTenant(): array;
}
