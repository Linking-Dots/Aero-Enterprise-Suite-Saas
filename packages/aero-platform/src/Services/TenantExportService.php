<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantExportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class TenantExportService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function request(Tenant $tenant, int $actorId): TenantExportRequest
    {
        return DB::transaction(function () use ($tenant, $actorId) {
            $req = TenantExportRequest::create([
                'tenant_id' => $tenant->id,
                'requested_by' => $actorId,
                'status' => 'pending',
                'expires_at' => now()->addDays(config('aero-platform.export_ttl_days', 7)),
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_EXPORT_REQUESTED->value,
                action: 'request',
                subject: $req,
                description: "Export requested for {$tenant->name}"
            );

            return $req;
        });
    }

    public function getStatus(Tenant $tenant): ?TenantExportRequest
    {
        return TenantExportRequest::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->first();
    }

    public function generateDownloadUrl(TenantExportRequest $request): string
    {
        return URL::temporarySignedRoute(
            'platform.admin.tenants.export.download',
            now()->addHours(config('aero-platform.export_download_link_ttl_hours', 2)),
            ['exportRequest' => $request->id]
        );
    }
}
