<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Attendance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Exceptions\AttendanceException;
use Aero\HRM\Models\ShiftSwapRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ShiftSwapService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * Create and persist a new shift swap request.
     */
    public function create(array $data): ShiftSwapRequest
    {
        return DB::transaction(function () use ($data): ShiftSwapRequest {
            $swap = ShiftSwapRequest::create($data);

            $this->audit->log(
                event: AuditEventType::RECORD_CREATED->value,
                action: 'created',
                subject: $swap,
                description: "Shift swap #{$swap->id} posted for {$swap->shift_date}",
                after: $swap->only(['requester_employee_id', 'shift_date', 'shift_label', 'status']),
            );

            return $swap;
        });
    }

    /**
     * Approve a shift swap request that is in 'open' or 'matched' status.
     *
     * @throws AttendanceException
     */
    public function approve(ShiftSwapRequest $swap): ShiftSwapRequest
    {
        $allowedStatuses = [ShiftSwapRequest::STATUS_OPEN, ShiftSwapRequest::STATUS_MATCHED];

        if (! in_array($swap->status, $allowedStatuses, strict: true)) {
            throw new AttendanceException(
                "Cannot approve shift swap request with status '{$swap->status}'."
            );
        }

        return DB::transaction(function () use ($swap): ShiftSwapRequest {
            $swap->status = ShiftSwapRequest::STATUS_APPROVED;
            $swap->approved_by = Auth::id();
            $swap->approved_at = now();
            $swap->save();

            $this->audit->log(
                event: AuditEventType::SHIFT_SWAP_APPROVED->value,
                action: 'approved',
                subject: $swap,
                description: "Shift swap request #{$swap->id} approved by user #".Auth::id().'.',
            );

            return $swap;
        });
    }
}
