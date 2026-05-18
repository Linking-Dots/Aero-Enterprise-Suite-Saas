<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Payroll;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Exceptions\PayrollLockedException;
use Aero\HRM\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles the approval workflow for a PayrollRun.
 *
 * Approval is a two-step write:
 *  1. Set approved_by / approved_at / status directly on the model and save()
 *     (these fields are intentionally NOT in $fillable to prevent mass-assignment).
 *  2. Write locked_at via DB::table() to bypass the model's updating guard
 *     (the guard only checks getOriginal('locked_at') which is still null at that point).
 */
final class PayrollApprovalService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function approve(PayrollRun $run): PayrollRun
    {
        if ($run->isLocked()) {
            throw new PayrollLockedException(
                "Payroll run #{$run->id} is already locked."
            );
        }

        if ($run->status === PayrollRun::STATUS_APPROVED) {
            throw new PayrollLockedException(
                "Payroll run #{$run->id} has already been approved."
            );
        }

        return DB::transaction(function () use ($run): PayrollRun {
            // Direct assignment — these fields are not in $fillable intentionally.
            $run->approved_by = Auth::id();
            $run->approved_at = now();
            $run->status      = PayrollRun::STATUS_APPROVED;
            $run->save();

            // Write locked_at via raw table query to bypass the model guard
            // (the guard fires on updating and reads getOriginal('locked_at') — still null here).
            DB::table('hrm_payroll_runs')
                ->where('id', $run->id)
                ->update(['locked_at' => now()]);

            // Refresh to get the persisted locked_at value.
            $run->refresh();

            $this->audit->log(
                event: AuditEventType::PAYROLL_RUN->value,
                action: 'approved',
                subject: $run,
                description: "Payroll run '{$run->label}' approved and locked.",
                after: [
                    'status'      => $run->status,
                    'approved_by' => $run->approved_by,
                    'approved_at' => $run->approved_at?->toIso8601String(),
                    'locked_at'   => $run->locked_at?->toIso8601String(),
                ],
            );

            return $run;
        });
    }
}
