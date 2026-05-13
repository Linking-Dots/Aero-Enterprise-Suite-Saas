<?php

declare(strict_types=1);

namespace Aero\HRM\Listeners;

use Aero\HRM\Events\ContractExpiring;
use Aero\HRM\Notifications\ContractExpiryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener that sends contract expiry notifications.
 */
class SendContractExpiryNotifications implements ShouldQueue
{
    /**
     * Handle the contract expiring event.
     */
    public function handle(ContractExpiring $event): void
    {
        $employee = $event->employee;
        $user = $employee->user;

        // Notify the employee
        if ($user) {
            $user->notify(new ContractExpiryNotification($employee, $event->daysRemaining));
        }

        // Notify the manager
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
                $manager->notify(new ContractExpiryNotification($employee, $daysRemaining));
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
                            $hrUser->notify(new ContractExpiryNotification($employee, $daysRemaining));
                        }
                    }

                    return;
                }
            } catch (\Throwable $e) {
                Log::warning('HRMAC not available for contract expiry notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: notify users with the HR Admin role
        \Aero\Core\Models\User::role('HR Admin')->get()
            ->each(function ($hrUser) use ($employee, $daysRemaining) {
                if ($hrUser->id !== $employee->user_id) {
                    $hrUser->notify(new ContractExpiryNotification($employee, $daysRemaining));
                }
            });
    }

    /**
     * Handle a failed job.
     */
    public function failed(ContractExpiring $event, \Throwable $exception): void
    {
        Log::error('Failed to send contract expiry notifications', [
            'employee_id' => $event->employee->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
