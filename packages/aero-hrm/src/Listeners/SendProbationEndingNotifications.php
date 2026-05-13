<?php

declare(strict_types=1);

namespace Aero\HRM\Listeners;

use Aero\HRM\Events\ProbationEnding;
use Aero\HRM\Notifications\ProbationEndingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener that sends probation ending notifications.
 */
class SendProbationEndingNotifications implements ShouldQueue
{
    /**
     * Handle the probation ending event.
     */
    public function handle(ProbationEnding $event): void
    {
        $employee = $event->employee;

        // Notify the employee's manager
        $this->notifyManager($employee, $event->daysRemaining);

        // Notify HR
        $this->notifyHr($employee, $event->daysRemaining);
    }

    /**
     * Notify the employee's manager.
     */
    protected function notifyManager($employee, int $daysRemaining): void
    {
        // manager_id references users.id directly
        if ($employee->manager_id) {
            $manager = \Aero\Core\Models\User::find($employee->manager_id);
            if ($manager) {
                $manager->notify(new ProbationEndingNotification($employee, $daysRemaining));
            }
        }
    }

    /**
     * Notify users with HRM employees access using HRMAC,
     * falling back to users with the "HR Admin" role when HRMAC is unavailable.
     */
    protected function notifyHr($employee, int $daysRemaining): void
    {
        if (app()->bound('Aero\Contracts\RoleModuleAccessInterface')) {
            try {
                $hrUsers = \Aero\HRMAC\Facades\HRMAC::getUsersWithSubModuleAccess('hrm', 'employees');

                if ($hrUsers->isNotEmpty()) {
                    foreach ($hrUsers as $hrUser) {
                        if ($hrUser->id !== $employee->user_id) {
                            $hrUser->notify(new ProbationEndingNotification($employee, $daysRemaining));
                        }
                    }

                    return;
                }
            } catch (\Throwable $e) {
                Log::warning('HRMAC not available for probation ending notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: notify users with the HR Admin role
        \Aero\Core\Models\User::role('HR Admin')->get()
            ->each(function ($hrUser) use ($employee, $daysRemaining) {
                if ($hrUser->id !== $employee->user_id) {
                    $hrUser->notify(new ProbationEndingNotification($employee, $daysRemaining));
                }
            });
    }

    /**
     * Handle a failed job.
     */
    public function failed(ProbationEnding $event, \Throwable $exception): void
    {
        Log::error('Failed to send probation ending notifications', [
            'employee_id' => $event->employee->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
