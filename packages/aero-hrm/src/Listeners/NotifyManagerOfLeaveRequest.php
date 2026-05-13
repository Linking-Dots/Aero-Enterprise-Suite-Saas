<?php

namespace Aero\HRM\Listeners;

use Aero\HRM\Events\Leave\LeaveRequested;
use Aero\HRM\Models\Employee;
use Aero\HRM\Notifications\LeaveRequestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyManagerOfLeaveRequest implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LeaveRequested $event): void
    {
        $leave = $event->leave;
        $user = $leave->user;

        // Skip if no user found
        if (! $user) {
            return;
        }

        // Get the employee record to find manager (direct query)
        $employee = Employee::where('user_id', $user->id)->first();

        // Find the employee's manager via configured user model (no direct cross-package import)
        $manager = $employee?->manager;

        if ($manager) {
            // Notify manager about leave request
            $manager->notify(new LeaveRequestNotification($leave));
        }

        // Notify users with access to leave management via HRMAC (replaces hardcoded HR roles)
        $this->notifyUsersWithLeaveAccess($leave, $user, $manager);
    }

    /**
     * Notify users who have access to leave management via HRMAC,
     * falling back to users with the "HR Admin" role when HRMAC is unavailable.
     */
    protected function notifyUsersWithLeaveAccess($leave, $employee, $manager): void
    {
        if (app()->bound('Aero\Contracts\RoleModuleAccessInterface')) {
            try {
                $hrmacService = app('Aero\Contracts\RoleModuleAccessInterface');

                // Get users with access to the 'leaves' submodule in 'hrm' module
                // Using 'approve' action to target users who can approve leave requests
                $usersWithAccess = $hrmacService->getUsersWithSubModuleAccess('hrm', 'leaves', 'approve');

                if ($usersWithAccess->isNotEmpty()) {
                    foreach ($usersWithAccess as $user) {
                        // Don't notify the employee requesting leave or the manager (already notified)
                        if ($user->id !== $employee->id && $user->id !== $manager?->id) {
                            $user->notify(new LeaveRequestNotification($leave));
                        }
                    }

                    return;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to get users with leave access via HRMAC', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: notify users with the HR Admin role
        \Aero\Core\Models\User::role('HR Admin')->get()
            ->each(function ($user) use ($leave, $employee, $manager) {
                if ($user->id !== $employee->id && $user->id !== $manager?->id) {
                    $user->notify(new LeaveRequestNotification($leave));
                }
            });
    }

    /**
     * Handle a job failure.
     */
    public function failed(LeaveRequested $event, \Throwable $exception): void
    {
        Log::error('Failed to notify manager of leave request', [
            'leave_id' => $event->leave->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
