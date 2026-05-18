<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Performance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Models\User;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\Feedback360Request;
use Aero\HRM\Notifications\Feedback360Invitation;
use Illuminate\Support\Facades\DB;

class Feedback360Service
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function open(array $payload): Feedback360Request
    {
        return DB::transaction(function () use ($payload): Feedback360Request {
            /** @var Feedback360Request $feedbackRequest */
            $feedbackRequest = Feedback360Request::create([
                'subject_employee_id' => $payload['subject_employee_id'],
                'requester_id' => $payload['requester_id'],
                'respondent_ids' => $payload['respondent_ids'] ?? [],
                'due_on' => $payload['due_on'],
                'status' => Feedback360Request::STATUS_OPEN,
            ]);

            // Notification stub — send invite to each respondent user
            $respondentIds = $feedbackRequest->respondent_ids ?? [];
            foreach ($respondentIds as $userId) {
                $user = User::find($userId);
                if ($user !== null) {
                    $user->notify(new Feedback360Invitation($feedbackRequest));
                }
            }

            $this->audit->log(
                event: AuditEventType::FEEDBACK_360_OPENED->value,
                action: 'feedback_360_opened',
                subject: $feedbackRequest,
                description: '360° feedback request opened with '.count($respondentIds).' respondent(s).',
            );

            return $feedbackRequest->fresh(['subject']);
        });
    }
}
