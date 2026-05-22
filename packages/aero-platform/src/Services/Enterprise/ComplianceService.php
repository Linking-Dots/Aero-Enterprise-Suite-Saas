<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\DpaTemplate;
use Aero\Platform\Models\Enterprise\DsarRequest;
use Aero\Platform\Models\Enterprise\TenantDpaSigning;
use Aero\Platform\Models\Enterprise\TosVersion;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ComplianceService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function getDpa(): ?DpaTemplate
    {
        return DpaTemplate::where('is_active', true)->latest()->first();
    }

    public function recordSigning(string $tenantId, string $templateId, array $signerData): TenantDpaSigning
    {
        return DB::transaction(function () use ($tenantId, $templateId, $signerData): TenantDpaSigning {
            $signing = TenantDpaSigning::create([
                'tenant_id' => $tenantId,
                'template_id' => $templateId,
                'signed_by_name' => $signerData['signed_by_name'],
                'signed_by_email' => $signerData['signed_by_email'],
                'signed_at' => now(),
                'ip_address' => $signerData['ip_address'] ?? null,
            ]);

            $this->audit->log(
                event: AuditEventType::DPA_SIGNED->value,
                action: 'sign',
                subject: $signing,
                description: "DPA signed for tenant {$tenantId} by {$signerData['signed_by_email']}"
            );

            return $signing;
        });
    }

    public function publishTosVersion(array $data): TosVersion
    {
        return DB::transaction(function () use ($data): TosVersion {
            $tos = TosVersion::create([
                'version' => $data['version'],
                'content_md' => $data['content_md'],
                'published_at' => now(),
                'requires_re_acceptance' => $data['requires_re_acceptance'] ?? false,
            ]);

            $this->audit->log(
                event: AuditEventType::TOS_PUBLISHED->value,
                action: 'publish',
                subject: $tos,
                description: "ToS version {$tos->version} published"
            );

            return $tos;
        });
    }

    public function requireReAcceptance(TosVersion $tos): TosVersion
    {
        return DB::transaction(function () use ($tos): TosVersion {
            $tos->update(['requires_re_acceptance' => true]);

            $this->audit->log(
                event: AuditEventType::TOS_RE_ACCEPTANCE_REQUIRED->value,
                action: 'require-acceptance',
                subject: $tos,
                description: "Re-acceptance required for ToS version {$tos->version}"
            );

            return $tos->refresh();
        });
    }

    public function listDsars(int $perPage = 25): LengthAwarePaginator
    {
        return DsarRequest::orderByDesc('created_at')->paginate($perPage);
    }

    public function fulfillDsar(DsarRequest $dsar, string $fulfilledBy, string $notes = ''): DsarRequest
    {
        return DB::transaction(function () use ($dsar, $fulfilledBy, $notes): DsarRequest {
            $dsar->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now(),
                'fulfilled_by' => $fulfilledBy,
                'notes' => $notes,
            ]);

            $this->audit->log(
                event: AuditEventType::DSAR_FULFILLED->value,
                action: 'fulfill',
                subject: $dsar,
                description: "DSAR {$dsar->id} fulfilled by {$fulfilledBy}"
            );

            return $dsar->refresh();
        });
    }
}
