<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\ObservabilityAlert;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ObservabilityService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function getApm(): array
    {
        return ['status' => 'nominal', 'fetched_at' => now()->toIso8601String()];
    }

    public function searchTraces(array $filters = []): array
    {
        return ['traces' => [], 'filters' => $filters];
    }

    public function getMetrics(): array
    {
        return ['metrics' => [], 'fetched_at' => now()->toIso8601String()];
    }

    public function aggregateLogs(array $filters = []): array
    {
        return ['logs' => [], 'filters' => $filters];
    }

    public function getAlerts(): Collection
    {
        return ObservabilityAlert::where('is_active', true)->get();
    }

    public function manageAlert(array $data): ObservabilityAlert
    {
        return DB::transaction(function () use ($data): ObservabilityAlert {
            $alert = ObservabilityAlert::create($data);

            $this->audit->log(
                event: AuditEventType::OBSERVABILITY_ALERT_CREATED->value,
                action: 'create',
                subject: $alert,
                description: "Observability alert '{$alert->name}' created"
            );

            return $alert;
        });
    }
}
