<?php

namespace Aero\HRM\Services\Disciplinary;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmDisciplinaryActionType;
use Aero\HRM\Models\HrmWarning;
use Illuminate\Support\Facades\DB;

final class WarningService
{
    public function __construct(
        private DisciplinaryCaseService $cases,
        private AuditServiceInterface $audit,
    ) {}

    public function issue(array $data): HrmWarning
    {
        return DB::transaction(function () use ($data) {
            $w = HrmWarning::create([...$data, 'status' => HrmWarning::STATUS_ISSUED, 'issued_at' => now()]);
            $this->audit->log(event: 'WARNING_ISSUED', action: 'issue', subject: $w, description: "Warning issued to employee {$w->employee_id}: {$w->subject}");
            $this->maybeEscalate($w);

            return $w;
        });
    }

    public function acknowledge(HrmWarning $w, ?string $response): void
    {
        abort_unless($w->status === HrmWarning::STATUS_ISSUED, 422, 'Warning already processed.');
        DB::transaction(function () use ($w, $response) {
            $w->update([
                'status' => HrmWarning::STATUS_ACKNOWLEDGED,
                'acknowledged_at' => now(),
                'employee_response' => $response,
            ]);
            $this->audit->log(event: 'WARNING_ACKNOWLEDGED', action: 'acknowledge', subject: $w, description: "Employee acknowledged warning: {$w->subject}");
        });
    }

    private function maybeEscalate(HrmWarning $w): void
    {
        $type = $w->actionType;
        if (! $type || ! $type->escalates_after_count) {
            return;
        }

        $count = HrmWarning::where('employee_id', $w->employee_id)
            ->where('action_type_id', $type->id)
            ->where('issued_at', '>=', now()->subYear())
            ->count();

        if ($count < $type->escalates_after_count) {
            return;
        }

        $toType = HrmDisciplinaryActionType::where('name', $type->escalates_to_type)->first();
        $case = $this->cases->open([
            'employee_id' => $w->employee_id,
            'action_type_id' => $toType?->id ?? $type->id,
            'opened_by' => auth()->id(),
            'incident_date' => now()->toDateString(),
            'subject' => "Auto-escalation: {$count}x {$type->name}",
            'description' => "Triggered by warning #{$w->id}.",
        ]);
        $w->update(['status' => HrmWarning::STATUS_ESCALATED, 'escalated_to_case_id' => $case->id]);
        $this->audit->log(event: 'WARNING_ESCALATED', action: 'escalate', subject: $w, description: "Warning escalated to case {$case->reference}");
    }
}
