<?php

namespace Aero\HRM\Listeners\Offboarding;

use Aero\HRM\Events\Offboarding\OffboardingCompleted;
use Aero\HRM\Notifications\Offboarding\OffboardingCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sends notification when offboarding is completed.
 */
class SendOffboardingCompletionNotification implements ShouldQueue
{
    public function handle(OffboardingCompleted $event): void
    {
        $offboarding = $event->offboarding;
        $employee = $offboarding->employee;

        if (! $employee) {
            Log::warning('Offboarding has no associated employee', [
                'offboarding_id' => $offboarding->id,
            ]);

            return;
        }

        try {
            // Notify the employee (if they still have access)
            $user = $employee->user;
            if ($user && ! $user->trashed()) {
                $user->notify(new OffboardingCompletedNotification($offboarding));
                $this->logNotification($user, $offboarding, 'offboarding.completed');
            }

            // Notify HR team
            $this->notifyHRTeam($offboarding);

            // Notify the employee's manager
            $this->notifyManager($offboarding, $employee);

            Log::info('Offboarding completion notifications sent', [
                'offboarding_id' => $offboarding->id,
                'employee_id' => $employee->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send offboarding completion notification', [
                'offboarding_id' => $offboarding->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function notifyHRTeam($offboarding): void
    {
        try {
            // Get users with HRM employee access using HRMAC
            $hrUsers = \Aero\HRMAC\Facades\HRMAC::getUsersWithSubModuleAccess('hrm', 'employees');

            foreach ($hrUsers as $hrUser) {
                $hrUser->notify(new OffboardingCompletedNotification($offboarding, [
                    'notification_target' => 'hr_team',
                ]));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify HR team of offboarding completion', [
                'offboarding_id' => $offboarding->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function notifyManager($offboarding, $employee): void
    {
        try {
            $manager = $employee->manager;

            if ($manager && $manager->user) {
                $manager->user->notify(new OffboardingCompletedNotification($offboarding, [
                    'notification_target' => 'manager',
                ]));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify manager of offboarding completion', [
                'offboarding_id' => $offboarding->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function logNotification($user, $offboarding, string $eventType): void
    {
        try {
            DB::table('notification_logs')->insert([
                'notifiable_type' => get_class($user),
                'notifiable_id' => $user->id,
                'notification_type' => OffboardingCompletedNotification::class,
                'event_type' => $eventType,
                'channel' => 'database',
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => json_encode([
                    'offboarding_id' => $offboarding->id,
                    'employee_id' => $offboarding->employee_id,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log notification', ['error' => $e->getMessage()]);
        }
    }

    public function failed(OffboardingCompleted $event, \Throwable $exception): void
    {
        Log::error('Failed to send offboarding completion notification', [
            'offboarding_id' => $event->offboarding->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
