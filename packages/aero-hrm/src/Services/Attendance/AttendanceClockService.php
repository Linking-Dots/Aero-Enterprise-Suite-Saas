<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Attendance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Exceptions\AttendanceException;
use Aero\HRM\Models\Attendance;
use Aero\HRM\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class AttendanceClockService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * Clock in an employee for today.
     *
     * @throws AttendanceException
     */
    public function clockIn(Employee $employee, string $source = 'web'): Attendance
    {
        $today = Carbon::today();

        $alreadyClockedIn = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNull('punchout')
            ->exists();

        if ($alreadyClockedIn) {
            throw new AttendanceException('Already clocked in today.');
        }

        return DB::transaction(function () use ($employee, $source, $today): Attendance {
            $now = Carbon::now();
            $status = $this->determineStatus($employee);

            $attendance = Attendance::create([
                'user_id' => $employee->user_id,
                'employee_id' => $employee->id,
                'date' => $today,
                'punchin' => $now,
                'status' => $status,
                'is_late' => $status === 'late',
                'is_manual' => false,
                'notes' => $source !== 'web' ? "Source: {$source}" : null,
            ]);

            $this->audit->log(
                event: AuditEventType::CLOCK_IN->value,
                action: 'clock_in',
                subject: $attendance,
                description: "Employee #{$employee->id} clocked in at {$now->toTimeString()} (status: {$status}, source: {$source}).",
            );

            return $attendance;
        });
    }

    /**
     * Clock out an employee for today's open attendance record.
     *
     * @throws AttendanceException
     */
    public function clockOut(Employee $employee): Attendance
    {
        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', Carbon::today())
            ->whereNull('punchout')
            ->first();

        if ($attendance === null) {
            throw new AttendanceException('No open clock-in found for today.');
        }

        return DB::transaction(function () use ($attendance): Attendance {
            $now = Carbon::now();

            /** @var \Carbon\Carbon $punchin */
            $punchin = Carbon::parse($attendance->punchin);
            $workHours = round($punchin->diffInMinutes($now) / 60, 2);

            $attendance->punchout = $now;
            $attendance->work_hours = $workHours;
            $attendance->save();

            $this->audit->log(
                event: AuditEventType::CLOCK_OUT->value,
                action: 'clock_out',
                subject: $attendance,
                description: "Employee #{$attendance->employee_id} clocked out at {$now->toTimeString()} (work_hours: {$workHours}).",
            );

            return $attendance;
        });
    }

    /**
     * Determine whether the employee is 'present' or 'late'.
     * Grace period: 15 minutes after the configured shift start (default 09:00).
     */
    public function determineStatus(Employee $employee): string
    {
        /** @var string $shiftStart */
        $shiftStart = config('hrm.shift_start', '09:00:00');

        $deadline = Carbon::parse(Carbon::today()->toDateString().' '.$shiftStart)
            ->addMinutes(15);

        return Carbon::now()->greaterThan($deadline) ? 'late' : 'present';
    }
}
