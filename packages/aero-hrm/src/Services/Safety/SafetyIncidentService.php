<?php

namespace Aero\HRM\Services\Safety;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmSafetyIncident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SafetyIncidentService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function report(array $data): HrmSafetyIncident
    {
        return DB::transaction(function () use ($data) {
            $incident = HrmSafetyIncident::create([
                ...$data,
                'reference' => 'INC-'.date('Y').'-'.strtoupper(Str::random(6)),
                'status' => HrmSafetyIncident::STATUS_REPORTED,
            ]);
            $this->audit->log(event: 'SAFETY_INCIDENT_REPORTED', action: 'report', subject: $incident, description: "Incident reported: {$incident->reference}");

            return $incident;
        });
    }

    public function addInvestigation(HrmSafetyIncident $incident, array $data): void
    {
        DB::transaction(function () use ($incident, $data) {
            $incident->investigations()->create($data);
            $incident->update(['status' => HrmSafetyIncident::STATUS_INVESTIGATING]);
            $this->audit->log(event: 'SAFETY_INCIDENT_INVESTIGATED', action: 'investigate', subject: $incident, description: "Investigation logged for: {$incident->reference}");
        });
    }

    public function close(HrmSafetyIncident $incident, int $userId): void
    {
        abort_if($incident->status === HrmSafetyIncident::STATUS_CLOSED, 422, 'Already closed.');
        DB::transaction(function () use ($incident, $userId) {
            $incident->update([
                'status' => HrmSafetyIncident::STATUS_CLOSED,
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);
            $this->audit->log(event: 'SAFETY_INCIDENT_CLOSED', action: 'close', subject: $incident, description: "Incident closed: {$incident->reference}");
        });
    }
}
