<?php

namespace Aero\HRM\Jobs;

use Aero\Core\Models\User;
use Aero\Notifications\Services\Push\FcmNotificationService;
use Aero\HRM\Models\AttendanceSetting;
use Aero\HRM\Models\Holiday;
use Aero\HRM\Models\Leave;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [30, 60, 120];

    protected $user;

    protected $attendanceSetting;

    public function __construct(User $user, AttendanceSetting $attendanceSetting)
    {
        $this->user = $user->withoutRelations();
        $this->attendanceSetting = $attendanceSetting->withoutRelations();
    }

    public function handle(FcmNotificationService $fcmService)
    {
        try {
            $today = Carbon::today();

            // Check if today is a holiday
            if ($this->isHoliday($today)) {
                Log::info('Skipping attendance reminder - today is a holiday', [
                    'user_id' => $this->user->id,
                    'date' => $today->toDateString(),
                ]);

                return;
            }

            // Check if user is on leave today
            if ($this->isUserOnLeave($today)) {
                Log::info('Skipping attendance reminder - user is on leave', [
                    'user_id' => $this->user->id,
                    'date' => $today->toDateString(),
                ]);

                return;
            }

            // Skip if user doesn't have a valid device token
            if (empty($this->user->fcm_token)) {
                Log::warning('Skipping attendance reminder - user has no FCM token', [
                    'user_id' => $this->user->id,
                ]);

                return;
            }

            // Prepare notification data
            $officeStartTime = $this->attendanceSetting->office_start_time->format('h:i A');
            $title = '⏰ Attendance Reminder';
            $body = sprintf("Office starts at %s. Don't be late!", $officeStartTime);

            // Prepare data payload
            $data = [
                'type' => 'attendance_reminder',
                'reminder_time' => now()->toDateTimeString(),
                'office_start_time' => $officeStartTime,
                'user_id' => (string) $this->user->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'sound' => 'default',
            ];

            // Send notification
            $result = $fcmService->sendNotification(
                $this->user->fcm_token,
                $title,
                $body,
                $data
            );

            if ($result) {
                Log::info('Attendance reminder sent successfully', [
                    'user_id' => $this->user->id,
                    'fcm_token' => $this->user->fcm_token,
                    'office_start_time' => $officeStartTime,
                ]);
            } else {
                Log::error('Failed to send attendance reminder', [
                    'user_id' => $this->user->id,
                    'fcm_token' => $this->user->fcm_token,
                ]);
                $this->fail('Failed to send FCM notification');
            }

        } catch (\Exception $e) {
            Log::error('Error in SendAttendanceReminder job', [
                'user_id' => $this->user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->fail($e);
        }
    }

    /**
     * Check if today is a holiday
     */
    protected function isHoliday($date)
    {
        return Holiday::whereDate('date', $date)->exists();
    }

    /**
     * Check if user is on leave today
     */
    protected function isUserOnLeave($date)
    {
        return Leave::where('user_id', $this->user->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        // Send user notification of failure, etc...
        Log::error('SendAttendanceReminder job failed', [
            'user_id' => $this->user->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
