<?php

declare(strict_types=1);

namespace Aero\HRM\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveBalance;
use Aero\HRM\Models\LeaveType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaveApplicationService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function calculateDays(Carbon $start, Carbon $end): float
    {
        return (float) ($start->diffInDays($end) + 1);
    }

    public function create(array $data): LeaveApplication
    {
        return DB::transaction(function () use ($data) {
            $start = Carbon::parse($data['start_date']);
            $end   = Carbon::parse($data['end_date']);

            if ($end->lt($start)) {
                throw new RuntimeException('End date cannot be before start date.');
            }

            $days = $this->calculateDays($start, $end);
            $type = LeaveType::findOrFail($data['leave_type_id']);

            $app = LeaveApplication::create([
                'employee_id'   => $data['employee_id'],
                'leave_type_id' => $type->id,
                'start_date'    => $start,
                'end_date'      => $end,
                'total_days'    => $days,
                'status'        => $type->requires_approval
                    ? LeaveApplication::STATUS_PENDING
                    : LeaveApplication::STATUS_APPROVED,
                'reason'        => $data['reason'] ?? null,
            ]);

            // Set audit fields directly (not in $fillable)
            if ($app->status === LeaveApplication::STATUS_APPROVED) {
                $app->approved_by = Auth::id();
                $app->approved_at = now();
                $app->save();
                $this->applyBalanceImpact($app, +1);
            }

            $this->audit->log(
                event: AuditEventType::RECORD_CREATED->value,
                action: 'created',
                subject: $app,
                description: "Leave application #{$app->id} submitted",
                after: $app->only(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days', 'status']),
            );

            return $app;
        });
    }

    public function approve(LeaveApplication $app): LeaveApplication
    {
        return DB::transaction(function () use ($app) {
            if ($app->status !== LeaveApplication::STATUS_PENDING) {
                throw new RuntimeException('Only pending applications can be approved.');
            }

            $app->status      = LeaveApplication::STATUS_APPROVED;
            $app->approved_by = Auth::id();
            $app->approved_at = now();
            $app->save();

            $this->applyBalanceImpact($app, +1);

            $this->audit->log(
                event: AuditEventType::LEAVE_APPROVED->value,
                action: 'approved',
                subject: $app,
                description: "Leave #{$app->id} approved",
            );

            return $app;
        });
    }

    public function reject(LeaveApplication $app, string $reason): LeaveApplication
    {
        return DB::transaction(function () use ($app, $reason) {
            if ($app->status !== LeaveApplication::STATUS_PENDING) {
                throw new RuntimeException('Only pending applications can be rejected.');
            }

            $app->status           = LeaveApplication::STATUS_REJECTED;
            $app->rejection_reason = $reason;
            $app->rejected_by      = Auth::id();
            $app->rejected_at      = now();
            $app->save();

            $this->audit->log(
                event: AuditEventType::LEAVE_REJECTED->value,
                action: 'rejected',
                subject: $app,
                description: "Leave #{$app->id} rejected",
                metadata: ['reason' => $reason],
            );

            return $app;
        });
    }

    public function cancel(LeaveApplication $app): LeaveApplication
    {
        return DB::transaction(function () use ($app) {
            $wasApproved = $app->status === LeaveApplication::STATUS_APPROVED;
            $app->status = LeaveApplication::STATUS_CANCELLED;
            $app->save();

            if ($wasApproved) {
                $this->applyBalanceImpact($app, -1);
            }

            $this->audit->log(
                event: AuditEventType::LEAVE_CANCELLED->value,
                action: 'cancelled',
                subject: $app,
                description: "Leave #{$app->id} cancelled",
            );

            return $app;
        });
    }

    private function applyBalanceImpact(LeaveApplication $app, int $direction): void
    {
        $year = $app->start_date->year;

        $balance = LeaveBalance::firstOrCreate(
            ['employee_id' => $app->employee_id, 'leave_type_id' => $app->leave_type_id, 'year' => $year],
            ['entitled' => (float) ($app->leaveType->days_per_year ?? 0)],
        );

        $balance->used = max(0.0, (float) $balance->used + ($direction * (float) $app->total_days));
        $balance->save();
    }
}
