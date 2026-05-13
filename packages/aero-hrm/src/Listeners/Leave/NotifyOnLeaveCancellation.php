<?php

declare(strict_types=1);

namespace Aero\HRM\Listeners\Leave;

use Aero\HRM\Events\Leave\LeaveCancelled;
use Aero\HRM\Notifications\LeaveCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener that sends notifications when a leave is cancelled.
 *
 * Notifies:
 * - The employee's manager (if the employee cancelled)
 * - The employee (if manager/HR cancelled)
 * - Users with access to the 'leaves' submodule in HRM (via HRMAC)
 */
class NotifyOnLeaveCancellation implements ShouldQueue
{
    /**
     * Handle the leave cancelled event.
     */
    public function handle(LeaveCancelled $event): void
    {
        $leave = $event->leave;
        $employee = $leave->user;

        if (! $employee) {
            Log::warning('LeaveCancelled event: No employee found for leave', [
                'leave_id' => $leave->id,
            ]);

            return;
        }

        // Determine who cancelled using the actor employee ID from the event
        $cancellerEmployeeId = $event->getActorEmployeeId();

        // Resolve the canceller's display name
        $cancelledByName = 'System';
        if ($cancellerEmployeeId) {
            $cancellerEmployee = \Aero\HRM\Models\Employee::find($cancellerEmployeeId);
            $cancelledByName = $cancellerEmployee?->user?->name ?? 'System';
        }

        // Compare canceller with leave owner to determine direction of cancellation
        $leaveOwnerEmployee = \Aero\HRM\Models\Employee::where('user_id', $employee->id)->first();
        $employeeSelfCancelled = $cancellerEmployeeId !== null
            && $leaveOwnerEmployee !== null
            && $cancellerEmployeeId === $leaveOwnerEmployee->id;

        // Notify the manager when the employee cancels their own leave
        $manager = $this->getManager($employee);
        if ($manager && $employeeSelfCancelled) {
            $manager->notify(new LeaveCancelledNotification($leave, $cancelledByName));
        }

        // Notify the employee if someone else (manager/HR) cancelled
        if (! $employeeSelfCancelled) {
            $employee->notify(new LeaveCancelledNotification($leave, $cancelledByName));
        }

        // Notify users with access to leave management (via HRMAC module access)
        $this->notifyUsersWithLeaveAccess($leave, $cancelledByName, $cancellerEmployeeId, $employee);
    }

    /**
     * Get the employee's manager.
     */
    protected function getManager(object $user): ?object
    {
        // Get the employee record to find manager (direct query)
        $employee = \Aero\HRM\Models\Employee::where('user_id', $user->id)->first();

        // manager_id references users table directly
        if ($employee && $employee->manager_id) {
            return \Aero\Core\Models\User::find($employee->manager_id);
        }

        return null;
    }

    /**
     * Notify users who have access to leave management via HRMAC.
     */
    protected function notifyUsersWithLeaveAccess($leave, string $cancelledByName, ?int $cancellerEmployeeId, $employee): void
    {
        // Use HRMAC to get users with access to hrm.leaves submodule
        if (! app()->bound('Aero\Contracts\RoleModuleAccessInterface')) {
            return;
        }

        // Resolve the canceller's user ID so we can exclude them from notifications
        $cancellerUserId = null;
        if ($cancellerEmployeeId) {
            $cancellerEmployee = \Aero\HRM\Models\Employee::find($cancellerEmployeeId);
            $cancellerUserId = $cancellerEmployee?->user_id;
        }

        try {
            $hrmacService = app('Aero\Contracts\RoleModuleAccessInterface');

            // Get users with access to the 'leaves' submodule in 'hrm' module
            $usersWithAccess = $hrmacService->getUsersWithSubModuleAccess('hrm', 'leaves');

            foreach ($usersWithAccess as $user) {
                // Don't notify if they're the one who cancelled or the employee
                if ($user->id !== $cancellerUserId && $user->id !== $employee->id) {
                    $user->notify(new LeaveCancelledNotification($leave, $cancelledByName));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to get users with leave access via HRMAC', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a failed job.
     */
    public function failed(LeaveCancelled $event, \Throwable $exception): void
    {
        Log::error('Failed to send leave cancellation notifications', [
            'leave_id' => $event->leave->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
