<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\ScimEndpoint;
use Aero\Platform\Models\Enterprise\ScimSyncLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnterpriseScimService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function configureEndpoint(string $tenantId, array $data): ScimEndpoint
    {
        return DB::transaction(function () use ($tenantId, $data): ScimEndpoint {
            $token = Str::random(64);

            $endpoint = ScimEndpoint::updateOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'token' => $token,
                    'provider' => $data['provider'] ?? 'okta',
                    'is_active' => true,
                ]
            );

            $this->audit->log(
                event: AuditEventType::SCIM_ENDPOINT_CONFIGURED->value,
                action: 'configure',
                subject: $endpoint,
                description: "SCIM endpoint configured for tenant {$tenantId}"
            );

            return $endpoint;
        });
    }

    public function rotateToken(ScimEndpoint $endpoint, string $rotatedBy): ScimEndpoint
    {
        return DB::transaction(function () use ($endpoint, $rotatedBy): ScimEndpoint {
            $endpoint->update([
                'token' => Str::random(64),
                'token_rotated_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::SCIM_TOKEN_ROTATED->value,
                action: 'rotate-token',
                subject: $endpoint,
                description: "SCIM token rotated for tenant {$endpoint->tenant_id} by {$rotatedBy}"
            );

            return $endpoint->refresh();
        });
    }

    public function listSyncLogs(string $tenantId, int $perPage = 25): LengthAwarePaginator
    {
        return ScimSyncLog::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getTokenForTenant(string $tenantId): ?string
    {
        return ScimEndpoint::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->value('token');
    }
}
