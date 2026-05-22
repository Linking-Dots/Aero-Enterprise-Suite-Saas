<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\DrDrill;
use Aero\Platform\Models\Enterprise\DrRunbook;
use Aero\Platform\Models\Enterprise\DrRunbookExecution;
use Aero\Platform\Models\Enterprise\RtoRpoConfig;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DisasterRecoveryService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function createRunbook(array $data, string $createdBy): DrRunbook
    {
        return DB::transaction(function () use ($data, $createdBy): DrRunbook {
            $runbook = DrRunbook::create($data);

            $this->audit->log(
                event: AuditEventType::DR_RUNBOOK_CREATED->value,
                action: 'create',
                subject: $runbook,
                description: "DR runbook '{$runbook->title}' created by {$createdBy}"
            );

            return $runbook;
        });
    }

    public function executeRunbook(DrRunbook $runbook, string $executedBy): DrRunbookExecution
    {
        return DB::transaction(function () use ($runbook, $executedBy): DrRunbookExecution {
            $execution = DrRunbookExecution::create([
                'runbook_id' => $runbook->id,
                'status' => 'running',
                'executed_by' => $executedBy,
                'started_at' => now(),
                'step_results' => [],
            ]);

            $stepResults = [];
            foreach ((array) $runbook->steps as $index => $step) {
                $stepResults[$index] = [
                    'step' => $step,
                    'status' => 'completed',
                    'order' => $index,
                    'executed_at' => now()->toIso8601String(),
                ];
            }

            $execution->update([
                'status' => 'completed',
                'step_results' => $stepResults,
                'completed_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::DR_RUNBOOK_EXECUTED->value,
                action: 'execute',
                subject: $execution,
                description: "DR runbook '{$runbook->title}' executed by {$executedBy}"
            );

            return $execution->refresh();
        });
    }

    public function setRtoRpo(string $service, int $rtoMinutes, int $rpoMinutes, string $updatedBy): RtoRpoConfig
    {
        return DB::transaction(function () use ($service, $rtoMinutes, $rpoMinutes, $updatedBy): RtoRpoConfig {
            $config = RtoRpoConfig::updateOrCreate(
                ['service' => $service],
                ['rto_minutes' => $rtoMinutes, 'rpo_minutes' => $rpoMinutes, 'updated_by' => $updatedBy]
            );

            $this->audit->log(
                event: AuditEventType::DR_RTO_RPO_CONFIGURED->value,
                action: 'configure',
                subject: $config,
                description: "RTO/RPO configured for {$service}: RTO={$rtoMinutes}m RPO={$rpoMinutes}m"
            );

            return $config;
        });
    }

    public function scheduleDrDrill(array $data, string $scheduledBy): DrDrill
    {
        return DB::transaction(function () use ($data, $scheduledBy): DrDrill {
            $drill = DrDrill::create([
                ...$data,
                'status' => 'scheduled',
                'scheduled_by' => $scheduledBy,
            ]);

            $this->audit->log(
                event: AuditEventType::DR_DRILL_SCHEDULED->value,
                action: 'schedule',
                subject: $drill,
                description: "DR drill scheduled by {$scheduledBy} at {$drill->scheduled_at}"
            );

            return $drill;
        });
    }

    public function listRunbooks(int $perPage = 25): LengthAwarePaginator
    {
        return DrRunbook::orderByDesc('created_at')->paginate($perPage);
    }
}
