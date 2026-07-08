<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Entitlement overrides — grant a tenant access to a module OUTSIDE a purchase
 * (comp / trial / grandfather / partner). Backed by the tenant_entitlements
 * ledger; ModuleEntitlementService already unions open source=override rows into
 * live entitlement, so a grant here takes effect on the tenant's next page load.
 *
 * All reads via the query builder (guard-free). Every mutation busts the tenant's
 * entitlement caches so access changes immediately, and is audit-logged.
 */
class EntitlementOverrideService
{
    public function __construct(private AuditServiceInterface $audit) {}

    /** @return array{kpis: array, overrides: array, ledger: array, tenantOptions: array, moduleOptions: array} */
    public function overview(): array
    {
        return [
            'kpis'          => $this->kpis(),
            'overrides'     => $this->openOverrides(),
            'ledger'        => $this->recentLedger(),
            'tenantOptions' => $this->tenantOptions(),
            'moduleOptions' => $this->moduleOptions(),
        ];
    }

    /** Open (in-effect) override grants, newest first, with the tenant name. */
    private function openOverrides(): array
    {
        return DB::table('tenant_entitlements as te')
            ->leftJoin('tenants as t', 't.id', '=', 'te.tenant_id')
            ->where('te.source', 'override')
            ->whereNull('te.revoked_at')
            ->orderByDesc('te.granted_at')
            ->get(['te.id', 'te.tenant_id', 't.name as tenant_name', 'te.module_code', 'te.reason', 'te.granted_at'])
            ->map(fn ($r) => [
                'id'          => $r->id,
                'tenant_id'   => $r->tenant_id,
                'tenant_name' => $r->tenant_name ?? $r->tenant_id ?? 'standalone',
                'module_code' => $r->module_code,
                'reason'      => $r->reason,
                'granted_at'  => $r->granted_at,
            ])
            ->all();
    }

    /** Recent ledger events across all sources — the audit feed. */
    private function recentLedger(): array
    {
        return DB::table('tenant_entitlements as te')
            ->leftJoin('tenants as t', 't.id', '=', 'te.tenant_id')
            ->orderByDesc('te.id')
            ->limit(25)
            ->get(['te.tenant_id', 't.name as tenant_name', 'te.module_code', 'te.source', 'te.granted_at', 'te.revoked_at'])
            ->map(fn ($r) => [
                'tenant_name' => $r->tenant_name ?? $r->tenant_id ?? 'standalone',
                'module_code' => $r->module_code,
                'source'      => $r->source,
                'state'       => $r->revoked_at ? 'revoked' : 'granted',
                'at'          => $r->revoked_at ?: $r->granted_at,
            ])
            ->all();
    }

    /** @return array<string, int> */
    private function kpis(): array
    {
        $open = DB::table('tenant_entitlements')->where('source', 'override')->whereNull('revoked_at');

        return [
            'active_overrides' => (int) (clone $open)->count(),
            'tenants'          => (int) (clone $open)->distinct()->count('tenant_id'),
            'ledger_events'    => (int) DB::table('tenant_entitlements')->count(),
        ];
    }

    private function tenantOptions(): array
    {
        return DB::table('tenants')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->all();
    }

    /** Non-core, active modules — the ones an override can grant. */
    private function moduleOptions(): array
    {
        return DB::table('modules')
            ->where('is_active', true)
            ->where('is_core', false)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn ($m) => ['code' => $m->code, 'name' => $m->name])
            ->all();
    }

    /**
     * Grant an override. Idempotent — refuses a duplicate open grant for the same
     * tenant + module.
     *
     * @throws \RuntimeException on a duplicate open grant
     */
    public function grant(string $tenantId, string $moduleCode, ?string $reason): void
    {
        $exists = DB::table('tenant_entitlements')
            ->where('tenant_id', $tenantId)
            ->where('module_code', $moduleCode)
            ->where('source', 'override')
            ->whereNull('revoked_at')
            ->exists();

        if ($exists) {
            throw new \RuntimeException("{$moduleCode} is already granted to this tenant.");
        }

        $now = now();
        DB::table('tenant_entitlements')->insert([
            'tenant_id'   => $tenantId,
            'module_code' => $moduleCode,
            'source'      => 'override',
            'source_id'   => null,
            'granted_at'  => $now,
            'revoked_at'  => null,
            'reason'      => $reason,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        $this->bust($tenantId);
        $this->audit->log(
            event: 'platform.entitlements.override_granted',
            action: 'grant',
            description: "Override: granted {$moduleCode} to tenant {$tenantId}".($reason ? " ({$reason})" : ''),
        );
    }

    /** Revoke (close) an open override row. */
    public function revoke(int $id): void
    {
        $row = DB::table('tenant_entitlements')->where('id', $id)->where('source', 'override')->first();
        if (! $row || $row->revoked_at) {
            return;
        }

        DB::table('tenant_entitlements')->where('id', $id)->update(['revoked_at' => now(), 'updated_at' => now()]);

        $this->bust($row->tenant_id);
        $this->audit->log(
            event: 'platform.entitlements.override_revoked',
            action: 'revoke',
            description: "Override: revoked {$row->module_code} from tenant {$row->tenant_id}",
        );
    }

    private function bust(?string $tenantId): void
    {
        $key = $tenantId ?? 'none';
        Cache::forget("tenant_subscribed_modules:{$key}");
        Cache::forget("module_entitlement:{$key}");
    }
}
