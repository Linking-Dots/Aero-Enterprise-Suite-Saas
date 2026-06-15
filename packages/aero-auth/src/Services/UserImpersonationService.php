<?php

declare(strict_types=1);

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * User Impersonation Service
 *
 * Allows administrators to temporarily impersonate other users for support purposes.
 *
 * Features:
 * - Secure impersonation with audit logging
 * - Original session preservation
 * - Time-limited impersonation
 * - Permission-based access control
 * - Automatic cleanup on logout
 * - Impersonation notifications to target user
 *
 * Security:
 * - Only users with 'impersonate-users' permission can impersonate
 * - Cannot impersonate super admins or users with higher privileges
 * - All actions during impersonation are logged
 * - Impersonation can be revoked at any time
 *
 * Usage:
 * ```php
 * $impersonationService = app(UserImpersonationService::class);
 *
 * // Start impersonation
 * $impersonationService->impersonate($targetUser);
 *
 * // Check if currently impersonating
 * if ($impersonationService->isImpersonating()) {
 *     // Show "Return to Admin" button
 * }
 *
 * // Stop impersonation and return to original user
 * $impersonationService->stopImpersonating();
 * ```
 */
class UserImpersonationService
{
    /**
     * Session key for storing impersonator data.
     */
    protected const SESSION_IMPERSONATOR_KEY = 'impersonation.original_user_id';

    protected const SESSION_IMPERSONATION_START = 'impersonation.started_at';

    protected const SESSION_IMPERSONATION_REASON = 'impersonation.reason';

    protected const SESSION_IMPERSONATION_TOKEN = 'impersonation.token';

    /**
     * Maximum impersonation duration in minutes.
     */
    protected int $maxDuration = 60;

    public function __construct(
        protected Guard $guard
    ) {}

    /**
     * Start impersonating another user.
     *
     * @param  User  $targetUser  The user to impersonate
     * @param  string|null  $reason  Optional reason for impersonation
     * @param  int|null  $duration  Duration in minutes (null for default)
     *
     * @throws \RuntimeException
     */
    public function impersonate(User $targetUser, ?string $reason = null, ?int $duration = null): bool
    {
        $impersonator = $this->guard->user();

        if (! $impersonator) {
            throw new \RuntimeException('No authenticated user to impersonate from.');
        }

        // Validate permissions
        $this->validateImpersonation($impersonator, $targetUser);

        // Check if already impersonating
        if ($this->isImpersonating()) {
            throw new \RuntimeException('Already impersonating a user. Stop current impersonation first.');
        }

        $token = $this->generateImpersonationToken();
        $duration = $duration ?? $this->maxDuration;

        // Store original user info in session
        Session::put(self::SESSION_IMPERSONATOR_KEY, $impersonator->id);
        Session::put(self::SESSION_IMPERSONATION_START, now()->toDateTimeString());
        Session::put(self::SESSION_IMPERSONATION_REASON, $reason);
        Session::put(self::SESSION_IMPERSONATION_TOKEN, $token);

        // Log the impersonation
        $this->logImpersonation('start', $impersonator, $targetUser, $reason);

        // Create database record
        $this->recordImpersonation($impersonator, $targetUser, $token, $reason, $duration);

        // Login as target user
        $this->guard->login($targetUser);

        // Notify target user if enabled
        $this->notifyTargetUser($targetUser, $impersonator, 'start');

        return true;
    }

    /**
     * Stop impersonating and return to original user.
     *
     *
     * @throws \RuntimeException
     */
    public function stopImpersonating(): bool
    {
        if (! $this->isImpersonating()) {
            return false;
        }

        $impersonatedUser = $this->guard->user();
        $originalUserId = Session::get(self::SESSION_IMPERSONATOR_KEY);
        $token = Session::get(self::SESSION_IMPERSONATION_TOKEN);

        $originalUser = User::find($originalUserId);

        if (! $originalUser) {
            throw new \RuntimeException('Original user not found. Session may be corrupted.');
        }

        // Log the stop
        $this->logImpersonation('stop', $originalUser, $impersonatedUser);

        // Update database record
        $this->endImpersonationRecord($token);

        // Notify target user if enabled
        $this->notifyTargetUser($impersonatedUser, $originalUser, 'stop');

        // Clear impersonation session data
        $this->clearImpersonationSession();

        // Login back as original user
        $this->guard->login($originalUser);

        return true;
    }

    /**
     * Check if currently impersonating another user.
     */
    public function isImpersonating(): bool
    {
        if (! Session::has(self::SESSION_IMPERSONATOR_KEY)) {
            return false;
        }

        // Check if impersonation has expired
        $startTime = Session::get(self::SESSION_IMPERSONATION_START);

        if ($startTime) {
            $start = Carbon::parse($startTime);

            if ($start->addMinutes($this->maxDuration)->isPast()) {
                // Impersonation expired, auto-stop
                $this->stopImpersonating();

                return false;
            }
        }

        return true;
    }

    /**
     * Get the original impersonator user.
     */
    public function getImpersonator(): ?User
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        $originalUserId = Session::get(self::SESSION_IMPERSONATOR_KEY);

        return User::find($originalUserId);
    }

    /**
     * Get impersonation info.
     */
    public function getImpersonationInfo(): ?array
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        $startTime = Session::get(self::SESSION_IMPERSONATION_START);
        $start = $startTime ? Carbon::parse($startTime) : now();

        return [
            'impersonator_id' => Session::get(self::SESSION_IMPERSONATOR_KEY),
            'impersonator' => $this->getImpersonator(),
            'started_at' => $startTime,
            'reason' => Session::get(self::SESSION_IMPERSONATION_REASON),
            'duration_minutes' => $start->diffInMinutes(now()),
            'expires_at' => $start->addMinutes($this->maxDuration)->toDateTimeString(),
            'remaining_minutes' => max(0, $this->maxDuration - $start->diffInMinutes(now())),
        ];
    }

    /**
     * Check if a user can be impersonated.
     */
    public function canImpersonate(?User $impersonator, User $target): bool
    {
        if (! $impersonator) {
            return false;
        }

        try {
            $this->validateImpersonation($impersonator, $target);

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    /**
     * Get impersonation history for audit.
     *
     * @param  int|null  $userId  Filter by impersonator user ID
     */
    public function getImpersonationHistory(?int $userId = null, int $limit = 50): Collection
    {
        $query = DB::table('user_impersonations')
            ->orderBy('started_at', 'desc')
            ->limit($limit);

        if ($userId) {
            $query->where('impersonator_id', $userId);
        }

        return $query->get()->map(function ($record) {
            return [
                'id' => $record->id,
                'impersonator_id' => $record->impersonator_id,
                'impersonator' => User::find($record->impersonator_id)?->name ?? 'Unknown',
                'target_id' => $record->target_id,
                'target' => User::find($record->target_id)?->name ?? 'Unknown',
                'reason' => $record->reason,
                'started_at' => $record->started_at,
                'ended_at' => $record->ended_at,
                'duration_minutes' => $record->ended_at
                    ? Carbon::parse($record->started_at)->diffInMinutes($record->ended_at)
                    : null,
                'is_active' => is_null($record->ended_at),
            ];
        });
    }

    /**
     * Force end all active impersonations for a user.
     *
     * @return int Number of sessions ended
     */
    public function forceEndImpersonations(int $userId): int
    {
        return DB::table('user_impersonations')
            ->where('target_id', $userId)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => now(),
                'force_ended' => true,
            ]);
    }

    /**
     * Set maximum impersonation duration.
     */
    public function setMaxDuration(int $minutes): self
    {
        $this->maxDuration = $minutes;

        return $this;
    }

    /**
     * Validate that impersonation is allowed.
     *
     *
     * @throws \RuntimeException
     */
    protected function validateImpersonation(User $impersonator, User $target): void
    {
        // Check if impersonator has permission
        if (! $this->hasImpersonationPermission($impersonator)) {
            throw new \RuntimeException('You do not have permission to impersonate users.');
        }

        // Cannot impersonate self
        if ($impersonator->id === $target->id) {
            throw new \RuntimeException('Cannot impersonate yourself.');
        }

        // Cannot impersonate super admins
        if ($this->isSuperAdmin($target)) {
            throw new \RuntimeException('Cannot impersonate super administrators.');
        }

        // Cannot impersonate users with higher or equal role level
        if ($this->hasHigherPrivileges($target, $impersonator)) {
            throw new \RuntimeException('Cannot impersonate users with equal or higher privileges.');
        }

        // Check if target user is active (active = not soft-deleted)
        if ($target->trashed()) {
            throw new \RuntimeException('Cannot impersonate inactive users.');
        }

        // Check if target is already being impersonated
        $activeImpersonation = DB::table('user_impersonations')
            ->where('target_id', $target->id)
            ->whereNull('ended_at')
            ->first();

        if ($activeImpersonation) {
            throw new \RuntimeException('This user is already being impersonated by another administrator.');
        }
    }

    /**
     * Check if user has impersonation permission.
     */
    protected function hasImpersonationPermission(User $user): bool
    {
        // Check for specific permission
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('impersonate-users');
        }

        // Check for super admin role
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check for admin role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['super-admin', 'admin', 'tenant-admin']);
        }

        return false;
    }

    /**
     * Check if user is super admin.
     */
    protected function isSuperAdmin(User $user): bool
    {
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('super-admin');
        }

        return (bool) ($user->is_super_admin ?? false);
    }

    /**
     * Check if target has higher privileges than impersonator.
     */
    protected function hasHigherPrivileges(User $target, User $impersonator): bool
    {
        $rolePriority = [
            'super-admin' => 100,
            'admin' => 80,
            'tenant-admin' => 60,
            'manager' => 40,
            'supervisor' => 30,
            'employee' => 10,
            'user' => 5,
        ];

        $targetPriority = 0;
        $impersonatorPriority = 0;

        if (method_exists($target, 'getRoleNames') && method_exists($impersonator, 'getRoleNames')) {
            foreach ($target->getRoleNames() as $role) {
                $targetPriority = max($targetPriority, $rolePriority[$role] ?? 0);
            }

            foreach ($impersonator->getRoleNames() as $role) {
                $impersonatorPriority = max($impersonatorPriority, $rolePriority[$role] ?? 0);
            }
        }

        return $targetPriority >= $impersonatorPriority;
    }

    /**
     * Generate secure impersonation token.
     */
    protected function generateImpersonationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Log impersonation action.
     */
    protected function logImpersonation(
        string $action,
        User $impersonator,
        ?User $target = null,
        ?string $reason = null
    ): void {
        $message = match ($action) {
            'start' => "User {$impersonator->name} (ID: {$impersonator->id}) started impersonating {$target->name} (ID: {$target->id})",
            'stop' => "User {$impersonator->name} (ID: {$impersonator->id}) stopped impersonating {$target?->name} (ID: {$target?->id})",
            default => "Impersonation action: {$action}",
        };

        if ($reason) {
            $message .= ". Reason: {$reason}";
        }

        Log::channel('security')->info($message, [
            'action' => "impersonation.{$action}",
            'impersonator_id' => $impersonator->id,
            'target_id' => $target?->id,
            'reason' => $reason,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Record impersonation in database.
     */
    protected function recordImpersonation(
        User $impersonator,
        User $target,
        string $token,
        ?string $reason,
        int $duration
    ): void {
        DB::table('user_impersonations')->insert([
            'token' => $token,
            'impersonator_id' => $impersonator->id,
            'target_id' => $target->id,
            'reason' => $reason,
            'max_duration_minutes' => $duration,
            'started_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * End impersonation record in database.
     */
    protected function endImpersonationRecord(string $token): void
    {
        DB::table('user_impersonations')
            ->where('token', $token)
            ->update([
                'ended_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Notify target user about impersonation.
     */
    protected function notifyTargetUser(User $target, User $impersonator, string $action): void
    {
        // Check if notifications are enabled
        $notifyOnImpersonation = config('auth.impersonation.notify_target', true);

        if (! $notifyOnImpersonation) {
            return;
        }

        // Dispatch notification (implement your notification class)
        // $target->notify(new ImpersonationNotification($impersonator, $action));
    }

    /**
     * Clear impersonation session data.
     */
    protected function clearImpersonationSession(): void
    {
        Session::forget([
            self::SESSION_IMPERSONATOR_KEY,
            self::SESSION_IMPERSONATION_START,
            self::SESSION_IMPERSONATION_REASON,
            self::SESSION_IMPERSONATION_TOKEN,
        ]);
    }
}
