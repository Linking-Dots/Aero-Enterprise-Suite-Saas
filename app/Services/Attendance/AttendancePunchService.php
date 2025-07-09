<?php

namespace App\Services\Attendance;

use App\Models\HRM\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling attendance punch operations
 */
class AttendancePunchService
{
    /**
     * Process punch in/out for a user
     */
    public function processPunch($user, Request $request): array
    {
        try {
            $today = Carbon::today();

            $existingAttendance = $this->getExistingAttendance($user->id, $today);

            if ($existingAttendance && !$existingAttendance->punchout) {
                return $this->punchOut($existingAttendance, $request);
            } else {
                return $this->punchIn($user, $today, $request);
            }

        } catch (\Exception $e) {
            Log::error('Attendance punch error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to record attendance. Please try again.',
                'code' => 500
            ];
        }
    }

    /**
     * Get existing attendance for user and date
     */
    private function getExistingAttendance(int $userId, Carbon $date): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('date', $date)
            ->latest()
            ->first();
    }

    /**
     * Process punch out
     */
    private function punchOut(Attendance $attendance, Request $request): array
    {
        $attendance->update([
            'punchout' => Carbon::now(),
            'punchout_location' => $this->formatLocation($request),
        ]);

        return [
            'status' => 'success',
            'message' => 'Successfully punched out!',
            'action' => 'punch_out',
            'attendance_id' => $attendance->id
        ];
    }

    /**
     * Process punch in
     */
    private function punchIn($user, Carbon $date, Request $request): array
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'punchin' => Carbon::now(),
            'punchin_location' => $this->formatLocation($request),
        ]);

        return [
            'status' => 'success',
            'message' => 'Successfully punched in!',
            'action' => 'punch_in',
            'attendance_id' => $attendance->id
        ];
    }    /**
     * Format location data from request
     */
    private function formatLocation(Request $request): ?string
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if (!$lat || !$lng) {
            return null;
        }

        $locationData = [
            'lat' => $lat,
            'lng' => $lng,
            'address' => $request->input('address', ''),
            'timestamp' => now()->toISOString()
        ];

        return json_encode($locationData);
    }
}
