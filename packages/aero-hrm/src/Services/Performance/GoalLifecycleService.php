<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Performance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\Goal;
use InvalidArgumentException;

class GoalLifecycleService
{
    private const VALID_FINAL_STATUSES = [
        Goal::STATUS_ACHIEVED,
        Goal::STATUS_MISSED,
        Goal::STATUS_CLOSED,
    ];

    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function close(Goal $goal, string $finalStatus): Goal
    {
        if (! in_array($finalStatus, self::VALID_FINAL_STATUSES, true)) {
            throw new InvalidArgumentException(
                "Invalid final status '{$finalStatus}'. Must be one of: ".implode(', ', self::VALID_FINAL_STATUSES)
            );
        }

        // Set closed_at directly — NOT in $fillable
        $goal->closed_at = now();
        $goal->status = $finalStatus;
        $goal->progress = $finalStatus === Goal::STATUS_ACHIEVED ? 100 : $goal->progress;
        $goal->save();

        $this->audit->log(
            event: AuditEventType::GOAL_CLOSED->value,
            action: 'goal_closed',
            subject: $goal,
            description: "Goal '{$goal->title}' closed with status '{$finalStatus}'.",
        );

        return $goal->fresh();
    }
}
