<?php

namespace Aero\HRM\Services\Safety;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmSafetyInspection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SafetyInspectionService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function schedule(array $data): HrmSafetyInspection
    {
        return DB::transaction(function () use ($data) {
            $inspection = HrmSafetyInspection::create([
                ...$data,
                'reference' => 'INSP-'.date('Y').'-'.strtoupper(Str::random(6)),
                'status' => HrmSafetyInspection::STATUS_SCHEDULED,
            ]);
            $this->audit->log(event: 'SAFETY_INSPECTION_SCHEDULED', action: 'schedule', subject: $inspection, description: "Inspection scheduled: {$inspection->reference}");

            return $inspection;
        });
    }

    public function submitFindings(HrmSafetyInspection $inspection, array $findings): void
    {
        DB::transaction(function () use ($inspection, $findings) {
            foreach ($findings as $finding) {
                $inspection->findings()->create($finding);
            }
            $inspection->update([
                'status' => HrmSafetyInspection::STATUS_COMPLETED,
                'conducted_date' => now()->toDateString(),
            ]);
            $this->audit->log(event: 'SAFETY_INSPECTION_COMPLETED', action: 'complete', subject: $inspection, description: "Inspection completed: {$inspection->reference} with ".count($findings).' findings');
        });
    }
}
