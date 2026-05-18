<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Performance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Exceptions\PerformanceFinalizedException;
use Aero\HRM\Models\HrmPerformanceReview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewSubmissionService
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function submitSelf(HrmPerformanceReview $review, array $answers): HrmPerformanceReview
    {
        $this->guardNotFinalized($review);

        return DB::transaction(function () use ($review, $answers) {
            $review->update([
                'self_answers' => $answers,
                'status' => HrmPerformanceReview::STATUS_SELF_SUBMITTED,
            ]);

            $this->audit->log(
                event: AuditEventType::RECORD_UPDATED->value,
                action: 'self_submitted',
                subject: $review,
                description: 'Employee submitted self-assessment.',
            );

            return $review->fresh();
        });
    }

    public function submitManager(HrmPerformanceReview $review, array $answers): HrmPerformanceReview
    {
        $this->guardNotFinalized($review);

        return DB::transaction(function () use ($review, $answers) {
            $review->update([
                'manager_answers' => $answers,
                'status' => HrmPerformanceReview::STATUS_MANAGER_SUBMITTED,
            ]);

            $this->audit->log(
                event: AuditEventType::RECORD_UPDATED->value,
                action: 'manager_submitted',
                subject: $review,
                description: 'Manager submitted review answers.',
            );

            return $review->fresh();
        });
    }

    public function finalize(HrmPerformanceReview $review, float $rating, string $comment): HrmPerformanceReview
    {
        if ($review->isFinalized()) {
            throw new PerformanceFinalizedException;
        }

        // Set audit/state fields directly — NOT via fill/update to bypass immutability guard
        $review->final_rating = $rating;
        $review->final_comment = $comment;
        $review->status = HrmPerformanceReview::STATUS_FINALIZED;
        $review->finalized_by = Auth::id();
        $review->finalized_at = now();
        $review->save();

        $this->audit->log(
            event: AuditEventType::PERFORMANCE_REVIEW_FINALIZED->value,
            action: 'finalized',
            subject: $review,
            description: "Performance review finalized with rating {$rating}.",
        );

        return $review->fresh();
    }

    private function guardNotFinalized(HrmPerformanceReview $review): void
    {
        if ($review->isFinalized()) {
            throw new PerformanceFinalizedException;
        }
    }
}
