<?php

declare(strict_types=1);

namespace Aero\Core\Hrmac;

use Aero\Contracts\AeroMode;
use Aero\Contracts\HrmacModelGuardInterface;
use Aero\Contracts\TenantScopeInterface;
use Aero\Core\ValueObjects\RequestContext;

/**
 * Consumer-supplied isolation guard for HRMAC's context-free models.
 *
 * HRMAC ships no context logic; the host (this package) decides it. This guard keeps
 * the exact tenant-isolation behavior the old TenantModel global scope had, and
 * additionally permits the SaaS platform/central context — the whole point of making
 * HRMAC shareable across tenants AND the platform.
 *
 * Allow when:
 *   - standalone (single DB; nothing to isolate), OR
 *   - the tenant scope is not yet bound (genuine early boot), OR
 *   - we are inside an initialized tenant context (tenant request), OR
 *   - we are inside the platform/central request context.
 * Otherwise fail closed (LogicException) — e.g. a SaaS query with no tenant and no
 * platform context, the pre-tenancy-init mistake the original guard existed to catch.
 */
class HrmacContextGuard implements HrmacModelGuardInterface
{
    public function assert(string $modelClass): void
    {
        // Standalone: one database, no isolation to enforce.
        if (! AeroMode::isSaas()) {
            return;
        }

        // Bypassed via AeroMode::withoutTenantContextGuard()
        $ref = new \ReflectionClass(AeroMode::class);
        $prop = $ref->getProperty('tenantContextChecker');
        $prop->setAccessible(true);
        if ($prop->getValue() === null) {
            return;
        }

        // Genuine early boot before the tenant scope is wired (mirrors the original
        // TenantModel guard's only legitimate allowance).
        if (! app()->bound(TenantScopeInterface::class)) {
            return;
        }

        // Tenant request: stancl/tenancy has switched the default connection.
        if (app(TenantScopeInterface::class)->inTenantContext()) {
            return;
        }

        // Platform/central request: a legitimate non-tenant context for shared HRMAC
        // tables (e.g. landlord role management on the central DB).
        if (app()->bound(RequestContext::class) && app(RequestContext::class)->isPlatform()) {
            return;
        }

        throw new \LogicException(
            $modelClass.' queried outside of a valid HRMAC context. '.
            'Ensure tenant requests run after tenancy middleware, or that platform '.
            'requests resolve RequestContext (scope=platform). For CLI/queue work on '.
            'central data, wrap in AeroMode::withoutTenantContextGuard().'
        );
    }
}
