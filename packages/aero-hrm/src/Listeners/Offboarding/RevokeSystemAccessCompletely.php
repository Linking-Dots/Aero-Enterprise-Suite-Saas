<?php

namespace Aero\HRM\Listeners\Offboarding;

use Aero\HRM\Events\Offboarding\OffboardingCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Completely revokes system access when offboarding is completed.
 */
class RevokeSystemAccessCompletely implements ShouldQueue
{
    public function handle(OffboardingCompleted $event): void
    {
        $offboarding = $event->offboarding;
        $employee = $offboarding->employee;

        if (! $employee) {
            Log::warning('Offboarding has no associated employee for access revocation', [
                'offboarding_id' => $offboarding->id,
            ]);

            return;
        }

        $user = $employee->user;

        if (! $user) {
            Log::info('Employee has no user account to revoke', [
                'employee_id' => $employee->id,
            ]);

            return;
        }

        try {
            // Deactivate user account (active/inactive is managed via SoftDeletes:
            // soft-deleting blocks login via the Eloquent auth provider's scope).
            Log::info('Deactivating user on offboarding completion', [
                'user_id' => $user->id,
                'reason' => "Offboarding completed - {$offboarding->reason}",
            ]);
            $user->delete();

            // Revoke all API tokens
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            // Remove all roles and permissions
            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([]);
            }

            if (method_exists($user, 'syncPermissions')) {
                $user->syncPermissions([]);
            }

            // Update employee status
            $employee->update([
                'status' => $this->getFinalStatus($offboarding->reason),
                'termination_date' => $offboarding->last_working_date ?? now(),
            ]);

            Log::info('System access completely revoked for offboarded employee', [
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'offboarding_id' => $offboarding->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke system access completely', [
                'offboarding_id' => $offboarding->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function getFinalStatus(string $reason): string
    {
        return match ($reason) {
            'resignation' => 'resigned',
            'termination' => 'terminated',
            'retirement' => 'retired',
            'contract_end' => 'contract_ended',
            default => 'inactive',
        };
    }

    public function failed(OffboardingCompleted $event, \Throwable $exception): void
    {
        Log::error('Failed to revoke system access completely', [
            'offboarding_id' => $event->offboarding->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
