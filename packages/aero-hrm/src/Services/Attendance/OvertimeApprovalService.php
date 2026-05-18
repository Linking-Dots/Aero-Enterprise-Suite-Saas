<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Attendance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Exceptions\AttendanceException;
use Aero\HRM\Models\OvertimeRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class OvertimeApprovalService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * Approve a pending overtime request.
     *
     * @throws AttendanceException
     */
    public function approve(OvertimeRequest $request): OvertimeRequest
    {
        if ($request->status !== OvertimeRequest::STATUS_PENDING) {
            throw new AttendanceException(
                "Cannot approve overtime request with status '{$request->status}'."
            );
        }

        return DB::transaction(function () use ($request): OvertimeRequest {
            $request->status = OvertimeRequest::STATUS_APPROVED;
            $request->approved_by = Auth::id();
            $request->approved_at = now();
            $request->save();

            $this->audit->log(
                event: AuditEventType::OVERTIME_APPROVED->value,
                action: 'approved',
                subject: $request,
                description: "Overtime request #{$request->id} approved by user #".Auth::id().'.',
            );

            return $request;
        });
    }

    /**
     * Reject a pending overtime request.
     *
     * @throws AttendanceException
     */
    public function reject(OvertimeRequest $request, string $reason): OvertimeRequest
    {
        if ($request->status !== OvertimeRequest::STATUS_PENDING) {
            throw new AttendanceException(
                "Cannot reject overtime request with status '{$request->status}'."
            );
        }

        return DB::transaction(function () use ($request, $reason): OvertimeRequest {
            $request->status = OvertimeRequest::STATUS_REJECTED;
            $request->approved_by = Auth::id();
            $request->approved_at = now();
            $request->rejection_reason = $reason;
            $request->save();

            $this->audit->log(
                event: AuditEventType::OVERTIME_REJECTED->value,
                action: 'rejected',
                subject: $request,
                description: "Overtime request #{$request->id} rejected by user #".Auth::id().". Reason: {$reason}",
            );

            return $request;
        });
    }
}
