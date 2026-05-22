<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Infra\PentestReport;
use Aero\Platform\Models\Infra\SecurityIncident;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Notifications\Infra\SecurityIncidentNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SecurityCenterService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function createIncident(array $data): SecurityIncident
    {
        return DB::transaction(function () use ($data): SecurityIncident {
            $incident = SecurityIncident::create($data);

            $this->audit->log(
                event: AuditEventType::SECURITY_INCIDENT_CREATED->value,
                action: 'create',
                subject: $incident,
                description: "Security incident '{$incident->title}' created (severity: {$incident->severity})"
            );

            return $incident;
        });
    }

    public function notifyTenants(SecurityIncident $incident): void
    {
        DB::transaction(function () use ($incident): void {
            $tenantIds = $incident->affected_tenants ?? [];

            if (empty($tenantIds)) {
                // Notify all active tenants
                $tenants = Tenant::where('status', 'active')->get();
            } else {
                $tenants = Tenant::whereIn('id', $tenantIds)->get();
            }

            Notification::send($tenants, new SecurityIncidentNotification($incident));

            $incident->update(['notified_tenants_at' => now()]);

            $this->audit->log(
                event: AuditEventType::SECURITY_INCIDENT_TENANTS_NOTIFIED->value,
                action: 'notify',
                subject: $incident,
                description: "Tenants notified for security incident '{$incident->title}' ({$tenants->count()} tenants)"
            );
        });
    }

    public function uploadPentest(array $data, UploadedFile $file): PentestReport
    {
        return DB::transaction(function () use ($data, $file): PentestReport {
            $path = $file->store('platform/pentests', 'local');

            $report = PentestReport::create(array_merge($data, [
                'file_path' => $path,
            ]));

            $this->audit->log(
                event: AuditEventType::PENTEST_REPORT_UPLOADED->value,
                action: 'upload',
                subject: $report,
                description: "Pentest report '{$report->title}' uploaded (conducted by: {$report->conducted_by})"
            );

            return $report;
        });
    }

    public function shareReport(PentestReport $report, array $tenantIds): PentestReport
    {
        return DB::transaction(function () use ($report, $tenantIds): PentestReport {
            $report->update(['shared_with_tenants' => $tenantIds]);

            $this->audit->log(
                event: AuditEventType::PENTEST_REPORT_SHARED->value,
                action: 'share',
                subject: $report,
                description: "Pentest report '{$report->title}' shared with ".count($tenantIds).' tenants'
            );

            return $report->refresh();
        });
    }

    /** @return array<string, mixed> */
    public function getDashboardSummary(): array
    {
        return [
            'open_incidents' => SecurityIncident::where('status', '!=', 'resolved')->count(),
            'critical_incidents' => SecurityIncident::where('severity', 'critical')->where('status', '!=', 'resolved')->count(),
            'recent_pentests' => PentestReport::orderByDesc('conducted_at')->limit(5)->get(['id', 'title', 'conducted_at', 'severity_summary']),
            'security_health_score' => $this->computeHealthScore(),
        ];
    }

    private function computeHealthScore(): int
    {
        $openCritical = SecurityIncident::where('severity', 'critical')->where('status', '!=', 'resolved')->count();
        $openHigh = SecurityIncident::where('severity', 'high')->where('status', '!=', 'resolved')->count();

        $deductions = ($openCritical * 20) + ($openHigh * 10);

        return max(0, 100 - $deductions);
    }
}
