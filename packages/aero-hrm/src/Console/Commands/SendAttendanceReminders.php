<?php

namespace Aero\HRM\Console\Commands;

use Aero\Core\Models\User;
use Aero\HRM\Jobs\SendAttendanceReminder;
use Aero\HRM\Models\AttendanceSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAttendanceReminders extends Command
{
    protected $signature = 'attendance:reminders
                            {--test : Run in test mode for a single user}
                            {--user-id= : Send to a specific user ID (for testing)}';

    protected $description = 'Send attendance reminders to all eligible users';

    public function handle()
    {
        $startTime = microtime(true);
        $this->info('🚀 Starting attendance reminders at '.now()->toDateTimeString());
        Log::info('Starting attendance reminders process');

        try {
            // Get the active attendance setting
            $attendanceSetting = AttendanceSetting::first();

            if (! $attendanceSetting) {
                $message = 'No active attendance setting found. Please configure attendance settings first.';
                $this->error($message);
                Log::error($message);

                return 1; // Exit with error code
            }

            $this->info('ℹ️ Using attendance setting ID: '.$attendanceSetting->id);

            // Handle test mode
            if ($this->option('test') || $this->option('user-id')) {
                return $this->handleTestMode($attendanceSetting);
            }

            // Get all active users with FCM tokens in chunks to handle memory efficiently
            $batchSize = 50;
            $totalUsers = 0;
            $dispatchedCount = 0;
            $skippedCount = 0;

            User::active()
                ->whereNotNull('fcm_token')
                ->chunk($batchSize, function ($users) use ($attendanceSetting, &$totalUsers, &$dispatchedCount, &$skippedCount) {
                    foreach ($users as $user) {
                        $totalUsers++;

                        // Skip if user has no FCM token
                        if (empty($user->fcm_token)) {
                            $skippedCount++;
                            $this->warn("Skipping user {$user->id}: No FCM token");

                            continue;
                        }

                        // Dispatch the job
                        SendAttendanceReminder::dispatch($user, $attendanceSetting)
                            ->onQueue('notifications');

                        $dispatchedCount++;
                        $this->info("✅ Dispatched reminder for user: {$user->name} (ID: {$user->id})");
                    }
                });

            $executionTime = round(microtime(true) - $startTime, 2);

            $summary = [
                'total_users' => $totalUsers,
                'dispatched' => $dispatchedCount,
                'skipped' => $skippedCount,
                'execution_time_seconds' => $executionTime,
            ];

            $this->info("\n📊 Summary:".json_encode($summary, JSON_PRETTY_PRINT));
            Log::info('Attendance reminders dispatched successfully', $summary);

            return 0; // Success

        } catch (\Exception $e) {
            $errorMessage = 'Error in attendance:reminders command: '.$e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1; // Exit with error code
        }
    }

    /**
     * Handle test mode for the command
     */
    protected function handleTestMode($attendanceSetting)
    {
        $userId = $this->option('user-id');

        if ($userId) {
            $user = User::find($userId);

            if (! $user) {
                $this->error("User with ID {$userId} not found");

                return 1;
            }

            $users = collect([$user]);
        } else {
            // Get a single test user with FCM token
            $user = User::active()
                ->whereNotNull('fcm_token')
                ->first();

            if (! $user) {
                $this->error('No active users with FCM tokens found for testing');

                return 1;
            }

            $users = collect([$user]);
        }

        $this->info("\n🧪 Running in test mode");
        $this->info("Sending test notification to: {$user->email} (ID: {$user->id})\n");

        foreach ($users as $user) {
            $job = new SendAttendanceReminder($user, $attendanceSetting);
            $job->handle(app()->make(\App\Services\Notification\FcmNotificationService::class));

            $this->info("✅ Test notification sent to: {$user->email}");
        }

        return 0;
    }
}
