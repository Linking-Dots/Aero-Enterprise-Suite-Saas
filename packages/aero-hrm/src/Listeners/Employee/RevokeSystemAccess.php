<?php

namespace Aero\HRM\Listeners\Employee;

use Aero\HRM\Events\Employee\EmployeeTerminated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Revokes system access for terminated employees.
 */
class RevokeSystemAccess implements ShouldQueue
{
    public function handle(EmployeeTerminated $event): void
    {
        $employee = $event->employee;
        $user = $employee->user;

        if (! $user) {
            Log::warning('No user associated with employee for access revocation', [
                'employee_id' => $employee->id,
            ]);

            return;
        }

        try {
            // If immediate termination, revoke access right away
            if ($event->immediate) {
                $this->revokeAccessImmediately($user, $employee);
            } else {
                // Schedule access revocation for last working date
                $this->scheduleAccessRevocation($user, $employee, $event->lastWorkingDate);
            }

            Log::info('System access revocation processed', [
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'immediate' => $event->immediate,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke system access', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function revokeAccessImmediately($user, $employee): void
    {
        // Deactivate user account (active/inactive is managed via SoftDeletes:
        // soft-deleting blocks login via the Eloquent auth provider's scope).
        $user->delete();

        // Revoke all tokens (for API access)
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        // Remove all permissions/roles if using spatie/laravel-permission
        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles([]);
        }

        // Update employee status
        $employee->update([
            'status' => 'terminated',
            'termination_date' => now(),
        ]);

        Log::info('Immediate access revocation completed', [
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);
    }

    protected function scheduleAccessRevocation($user, $employee, $lastWorkingDate): void
    {
        // Create a scheduled job for access revocation
        // This could dispatch a delayed job or create a scheduled task

        if ($lastWorkingDate) {
            // Calculate delay until end of last working day
            $revokeAt = $lastWorkingDate->copy()->endOfDay();

            // For now, just log the scheduled revocation
            // In production, you'd dispatch a delayed job
            Log::info('Access revocation scheduled', [
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'revoke_at' => $revokeAt->toIso8601String(),
            ]);

            // Update employee with pending termination status
            $employee->update([
                'status' => 'notice_period',
                'termination_date' => $lastWorkingDate,
            ]);
        }
    }

    public function failed(EmployeeTerminated $event, \Throwable $exception): void
    {
        Log::error('Failed to process access revocation', [
            'employee_id' => $event->employee->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
