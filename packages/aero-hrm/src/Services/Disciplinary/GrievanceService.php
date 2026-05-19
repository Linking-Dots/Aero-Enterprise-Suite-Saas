<?php

namespace Aero\HRM\Services\Disciplinary;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmGrievance;
use Illuminate\Support\Facades\DB;

final class GrievanceService
{
    public function __construct(
        private ReferenceGenerator $refs,
        private AuditServiceInterface $audit,
    ) {}

    public function file(array $data): HrmGrievance
    {
        return DB::transaction(function () use ($data) {
            $g = HrmGrievance::create([
                ...$data,
                'reference' => $this->refs->grievance(),
                'status' => HrmGrievance::STATUS_FILED,
            ]);
            $this->addEvent($g, 'filed', ['category' => $g->category]);
            $this->audit->log(event: 'GRIEVANCE_FILED', action: 'file', subject: $g, description: "Grievance filed: {$g->reference}");

            return $g;
        });
    }

    public function assignInvestigator(HrmGrievance $g, int $userId, int $byUserId): void
    {
        DB::transaction(function () use ($g, $userId, $byUserId) {
            $g->update(['investigator_id' => $userId, 'status' => HrmGrievance::STATUS_UNDER_INVESTIGATION]);
            $this->addEvent($g, 'assigned', ['investigator_id' => $userId], $byUserId);
            $this->audit->log(event: 'GRIEVANCE_ASSIGNED', action: 'assign', subject: $g, description: "Investigator assigned to grievance: {$g->reference}");
        });
    }

    public function resolve(HrmGrievance $g, string $notes, int $userId): void
    {
        abort_unless($g->status === HrmGrievance::STATUS_UNDER_INVESTIGATION, 422, 'Grievance must be under investigation to resolve.');
        DB::transaction(function () use ($g, $notes, $userId) {
            $g->update(['status' => HrmGrievance::STATUS_RESOLVED, 'resolution_notes' => $notes, 'resolved_at' => now()]);
            $this->addEvent($g, 'resolved', ['notes' => $notes], $userId);
            $this->audit->log(event: 'GRIEVANCE_RESOLVED', action: 'resolve', subject: $g, description: "Grievance resolved: {$g->reference}");
        });
    }

    public function dismiss(HrmGrievance $g, string $reason, int $userId): void
    {
        abort_if(
            in_array($g->status, [HrmGrievance::STATUS_RESOLVED, HrmGrievance::STATUS_DISMISSED], true),
            422,
            'Grievance already resolved or dismissed.'
        );
        DB::transaction(function () use ($g, $reason, $userId) {
            $g->update(['status' => HrmGrievance::STATUS_DISMISSED, 'resolution_notes' => $reason, 'resolved_at' => now()]);
            $this->addEvent($g, 'dismissed', ['reason' => $reason], $userId);
            $this->audit->log(event: 'GRIEVANCE_DISMISSED', action: 'dismiss', subject: $g, description: "Grievance dismissed: {$g->reference}");
        });
    }

    private function addEvent(HrmGrievance $g, string $type, array $payload, ?int $userId = null): void
    {
        $g->events()->create([
            'type' => $type,
            'payload' => $payload,
            'actor_id' => $userId ?? auth()->id(),
            'occurred_at' => now(),
        ]);
    }
}
