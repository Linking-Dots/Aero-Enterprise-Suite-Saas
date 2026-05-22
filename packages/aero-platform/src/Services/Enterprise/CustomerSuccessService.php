<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\CsmAssignment;
use Aero\Platform\Models\Enterprise\NpsSurveyResponse;
use Aero\Platform\Models\Enterprise\PlaybookRun;
use Aero\Platform\Models\Enterprise\SuccessPlaybook;
use Aero\Platform\Models\Enterprise\TenantHealthScore;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerSuccessService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function computeHealthScores(): void
    {
        DB::transaction(function (): void {
            $this->audit->log(
                event: AuditEventType::CS_HEALTH_SCORES_COMPUTED->value,
                action: 'compute',
                description: 'Tenant health scores computation triggered'
            );
        });
    }

    public function getHealthScores(int $perPage = 25): LengthAwarePaginator
    {
        return TenantHealthScore::orderByDesc('score')->paginate($perPage);
    }

    public function upsertHealthScore(string $tenantId, array $signals): TenantHealthScore
    {
        return DB::transaction(function () use ($tenantId, $signals): TenantHealthScore {
            $score = $this->calculateScore($signals);

            $health = TenantHealthScore::updateOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'score' => $score,
                    'signals' => $signals,
                    'trend' => $this->determineTrend($tenantId, $score),
                    'computed_at' => now(),
                ]
            );

            $this->audit->log(
                event: AuditEventType::CS_HEALTH_SCORES_COMPUTED->value,
                action: 'compute',
                subject: $health,
                description: "Health score computed for tenant {$tenantId}: {$score}"
            );

            return $health;
        });
    }

    public function churnRiskAnalysis(): array
    {
        return TenantHealthScore::where('score', '<', 40)
            ->orWhere('trend', 'down')
            ->orderBy('score')
            ->get(['tenant_id', 'score', 'trend', 'computed_at'])
            ->toArray();
    }

    public function sendNpsSurvey(string $tenantId, string $userEmail): NpsSurveyResponse
    {
        return DB::transaction(function () use ($tenantId, $userEmail): NpsSurveyResponse {
            $response = NpsSurveyResponse::create([
                'tenant_id' => $tenantId,
                'user_email' => $userEmail,
                'score' => 0,
                'sent_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::CS_NPS_SURVEY_SENT->value,
                action: 'send',
                subject: $response,
                description: "NPS survey sent to {$userEmail} for tenant {$tenantId}"
            );

            return $response;
        });
    }

    public function getNpsResults(int $perPage = 25): LengthAwarePaginator
    {
        return NpsSurveyResponse::whereNotNull('responded_at')
            ->orderByDesc('responded_at')
            ->paginate($perPage);
    }

    public function assignCsm(string $tenantId, string $csmUserId): void
    {
        DB::transaction(function () use ($tenantId, $csmUserId): void {
            $assignment = CsmAssignment::updateOrCreate(
                ['tenant_id' => $tenantId],
                ['csm_user_id' => $csmUserId, 'assigned_at' => now()]
            );

            $this->audit->log(
                event: AuditEventType::CS_CSM_ASSIGNED->value,
                action: 'assign',
                subject: $assignment,
                description: "CSM {$csmUserId} assigned to tenant {$tenantId}"
            );
        });
    }

    public function executePlaybook(SuccessPlaybook $playbook, string $tenantId, string $executedBy): PlaybookRun
    {
        return DB::transaction(function () use ($playbook, $tenantId, $executedBy): PlaybookRun {
            $run = PlaybookRun::create([
                'playbook_id' => $playbook->id,
                'tenant_id' => $tenantId,
                'status' => 'running',
                'executed_by' => $executedBy,
                'started_at' => now(),
                'step_results' => [],
            ]);

            $stepResults = [];
            foreach ((array) $playbook->steps as $index => $step) {
                $stepResults[$index] = ['step' => $step, 'status' => 'completed', 'executed_at' => now()->toIso8601String()];
            }

            $run->update([
                'status' => 'completed',
                'step_results' => $stepResults,
                'completed_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::CS_PLAYBOOK_EXECUTED->value,
                action: 'execute',
                subject: $run,
                description: "Playbook '{$playbook->name}' executed for tenant {$tenantId}"
            );

            return $run->refresh();
        });
    }

    public function listPlaybooks(int $perPage = 25): LengthAwarePaginator
    {
        return SuccessPlaybook::where('is_active', true)->paginate($perPage);
    }

    private function calculateScore(array $signals): int
    {
        $weights = [
            'login_frequency' => 30,
            'feature_breadth' => 30,
            'support_tickets' => 20,
            'payment_history' => 20,
        ];

        $score = 0;
        foreach ($weights as $key => $weight) {
            $signal = (float) ($signals[$key] ?? 0);
            $score += (int) round(($signal / 100) * $weight);
        }

        return max(0, min(100, $score));
    }

    private function determineTrend(string $tenantId, int $newScore): string
    {
        $existing = TenantHealthScore::where('tenant_id', $tenantId)->first();
        if (! $existing) {
            return 'stable';
        }

        $diff = $newScore - $existing->score;
        if ($diff >= 5) {
            return 'up';
        }
        if ($diff <= -5) {
            return 'down';
        }

        return 'stable';
    }
}
