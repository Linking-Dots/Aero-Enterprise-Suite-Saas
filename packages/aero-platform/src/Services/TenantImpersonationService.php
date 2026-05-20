<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantImpersonationService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function start(Tenant $tenant, int $actorId): string
    {
        $token = (string) Str::uuid();

        DB::transaction(function () use ($tenant, $actorId, $token) {
            session(['impersonation' => [
                'tenant_id' => $tenant->id,
                'actor_id' => $actorId,
                'token' => $token,
                'started' => now()->toISOString(),
            ]]);

            $this->audit->log(
                event: 'TENANT_IMPERSONATION_STARTED',
                action: 'impersonate',
                subject: $tenant,
                description: "Actor {$actorId} started impersonating {$tenant->name}"
            );
        });

        return $token;
    }

    public function end(string $token): void
    {
        $sess = session('impersonation');

        if (! $sess || $sess['token'] !== $token) {
            return;
        }

        $tenant = Tenant::find($sess['tenant_id']);

        if ($tenant) {
            $this->audit->log(
                event: 'TENANT_IMPERSONATION_ENDED',
                action: 'impersonate',
                subject: $tenant,
                description: "Actor {$sess['actor_id']} ended impersonation of {$tenant->name}"
            );
        }

        session()->forget('impersonation');
    }
}
