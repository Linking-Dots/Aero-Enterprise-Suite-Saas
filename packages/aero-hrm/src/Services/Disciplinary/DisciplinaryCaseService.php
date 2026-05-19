<?php

namespace Aero\HRM\Services\Disciplinary;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmDisciplinaryCase;
use Illuminate\Support\Facades\DB;

final class DisciplinaryCaseService
{
    public function __construct(
        private ReferenceGenerator $refs,
        private AuditServiceInterface $audit,
    ) {}

    public function open(array $data): HrmDisciplinaryCase
    {
        return DB::transaction(function () use ($data) {
            $case = HrmDisciplinaryCase::create([
                ...$data,
                'reference' => $this->refs->case(),
                'status' => HrmDisciplinaryCase::STATUS_OPEN,
            ]);
            $this->addEvent($case, 'opened', ['subject' => $case->subject]);
            $this->audit->log(event: 'DISCIPLINARY_CASE_OPENED', action: 'open', subject: $case, description: "Opened case: {$case->reference}");

            return $case;
        });
    }

    public function respond(HrmDisciplinaryCase $case, string $response, int $userId): void
    {
        abort_unless(in_array($case->status, [HrmDisciplinaryCase::STATUS_OPEN, HrmDisciplinaryCase::STATUS_AWAITING_RESPONSE]), 422, 'Cannot respond in current state.');
        DB::transaction(function () use ($case, $response, $userId) {
            $case->update(['status' => HrmDisciplinaryCase::STATUS_UNDER_REVIEW]);
            $this->addEvent($case, 'response', ['text' => $response], $userId);
            $this->audit->log(event: 'DISCIPLINARY_CASE_RESPONDED', action: 'respond', subject: $case, description: "Response recorded for case: {$case->reference}");
        });
    }

    public function close(HrmDisciplinaryCase $case, string $outcome, ?string $notes, int $userId): void
    {
        abort_if($case->status === HrmDisciplinaryCase::STATUS_CLOSED, 422, 'Already closed.');
        DB::transaction(function () use ($case, $outcome, $notes, $userId) {
            $case->update([
                'status' => HrmDisciplinaryCase::STATUS_CLOSED,
                'outcome' => $outcome,
                'closure_notes' => $notes,
                'closed_at' => now(),
                'closed_by' => $userId,
            ]);
            $this->addEvent($case, 'closed', ['outcome' => $outcome, 'notes' => $notes], $userId);
            $this->audit->log(event: 'DISCIPLINARY_CASE_CLOSED', action: 'close', subject: $case, description: "Closed case {$case->reference} with outcome: {$outcome}");
        });
    }

    public function addEvent(HrmDisciplinaryCase $case, string $type, array $payload = [], ?int $userId = null): void
    {
        $case->events()->create([
            'type' => $type,
            'payload' => $payload,
            'actor_id' => $userId ?? auth()->id(),
            'occurred_at' => now(),
        ]);
    }
}
