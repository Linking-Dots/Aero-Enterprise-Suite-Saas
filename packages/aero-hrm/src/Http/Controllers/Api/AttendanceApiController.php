<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Api;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Attendance;
use Aero\HRM\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HRM Push H.T2 — Attendance REST API.
 *
 * Mobile clock-in/clock-out + own-attendance read. The owner-only
 * scope is the safety guarantee: a token can NEVER read another user's
 * attendance unless the holder also has hrm.attendance.daily.list.view
 * (admin-level permission).
 */
class AttendanceApiController extends Controller
{
    /**
     * GET /api/hrm/attendance/today
     *
     * Returns today's attendance record for the authenticated user.
     */
    public function today(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();

        if (! $employee) {
            return response()->json([
                'error' => 'No employee profile for this user.',
            ], 404);
        }

        $today = now()->toDateString();
        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return response()->json([
            'data' => $attendance ? $this->transform($attendance) : null,
            'date' => $today,
        ]);
    }

    /**
     * POST /api/hrm/attendance/clock-in
     *
     * Records a clock-in for the authenticated user's current-day
     * attendance. Idempotent: if a record already exists, returns it
     * with 200; never creates duplicates.
     */
    public function clockIn(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->firstOrFail();

        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $today],
            [
                'clock_in_at' => now(),
                'status'      => 'present',
                'source'      => 'api',
                'ip_address'  => $request->ip(),
            ]
        );

        return response()->json(['data' => $this->transform($attendance)], $attendance->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * POST /api/hrm/attendance/clock-out
     *
     * Records a clock-out for the authenticated user's current-day
     * attendance. 422 if no clock-in exists for today.
     */
    public function clockOut(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->firstOrFail();

        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance) {
            return response()->json([
                'error' => 'Cannot clock out without a clock-in record for today.',
            ], 422);
        }

        if ($attendance->clock_out_at !== null) {
            // Already clocked out — idempotent return
            return response()->json(['data' => $this->transform($attendance)]);
        }

        $attendance->forceFill([
            'clock_out_at' => now(),
        ])->save();

        return response()->json(['data' => $this->transform($attendance->fresh())]);
    }

    protected function transform(Attendance $a): array
    {
        return [
            'id'           => $a->id,
            'employee_id'  => $a->employee_id,
            'date'         => optional($a->date)->toDateString(),
            'clock_in_at'  => optional($a->clock_in_at)->toIso8601String(),
            'clock_out_at' => optional($a->clock_out_at)->toIso8601String(),
            'status'       => $a->status,
            'source'       => $a->source ?? null,
        ];
    }
}
