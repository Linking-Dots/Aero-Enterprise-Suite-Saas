<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Carbon;

class TenantImpersonationService
{
    private const TTL_MINUTES = 60;

    public function __construct(private AuditServiceInterface $audit) {}

    public function start(Tenant $tenant, int $actorId): string
    {
        $token = bin2hex(random_bytes(32));

        session(['impersonation' => [
            'tenant_id' => $tenant->id,
            'actor_id' => $actorId,
            'token' => $token,
            'started' => now()->toISOString(),
        ]]);

        $this->audit->log(
            event: AuditEventType::TENANT_IMPERSONATION_STARTED->value,
            action: 'impersonate',
            subject: $tenant,
            description: "Actor {$actorId} started impersonating {$tenant->name}"
        );

        return $token;
    }

    public function end(string $token): void
    {
        $sess = session('impersonation');

        if (! $sess || $sess['token'] !== $token) {
            return;
        }

        $startedAt = Carbon::parse($sess['started']);

        if ($startedAt->diffInMinutes(now()) > self::TTL_MINUTES) {
            session()->forget('impersonation');

            return;
        }

        $tenant = Tenant::find($sess['tenant_id']);

        if ($tenant) {
            $this->audit->log(
                event: AuditEventType::TENANT_IMPERSONATION_ENDED->value,
                action: 'impersonate',
                subject: $tenant,
                description: "Actor {$sess['actor_id']} ended impersonation of {$tenant->name}"
            );
        }

        session()->forget('impersonation');
    }
}
