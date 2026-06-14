<?php

declare(strict_types=1);

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Aero\Kernel\Support\TenantCache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Password Policy Service
 *
 * Manages configurable password policies for tenants.
 * Enforces password requirements, expiration, and history.
 *
 * Features:
 * - Configurable password requirements (length, complexity)
 * - Password expiration policies
 * - Password history to prevent reuse
 * - Common password blacklist
 * - Tenant-specific policy overrides
 *
 * Usage:
 * ```php
 * $policyService = app(PasswordPolicyService::class);
 *
 * // Validate a password against policy
 * $result = $policyService->validate('newPassword123!', $user);
 * if (!$result['valid']) {
 *     return response()->json(['errors' => $result['errors']], 422);
 * }
 *
 * // Check if password has expired
 * if ($policyService->isPasswordExpired($user)) {
 *     return redirect()->route('password.change');
 * }
 *
 * // Record password change in history
 * $policyService->recordPasswordChange($user, $hashedPassword);
 * ```
 */
class PasswordPolicyService
{
    /**
     * Default password policy settings.
     */
    protected array $defaultPolicy = [
        'min_length' => 8,
        'max_length' => 128,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
        'password_expiry_days' => 0, // 0 = never expires
        'password_history_count' => 5, // Remember last N passwords
        'prevent_common_passwords' => true,
        'prevent_username_in_password' => true,
        'max_consecutive_chars' => 3, // Max repeated chars (e.g., 'aaa')
    ];

    /**
     * Common weak passwords to block.
     */
    protected array $commonPasswords = [
        'password', 'password1', 'password123', '123456', '12345678',
        'qwerty', 'abc123', 'monkey', 'master', 'dragon',
        'letmein', 'login', 'welcome', 'admin', 'administrator',
        'passw0rd', 'Password1', 'password!', 'changeme',
    ];

    /**
     * Validate a password against the current policy.
     *
     * @param  string  $password  The plain text password
     * @param  User|null  $user  The user (for history/username checks)
     * @param  array|null  $customPolicy  Override default policy
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(
        string $password,
        ?User $user = null,
        ?array $customPolicy = null
    ): array {
        $policy = $customPolicy ?? $this->getPolicy($user);
        $errors = [];

        // Length checks
        if (strlen($password) < $policy['min_length']) {
            $errors[] = "Password must be at least {$policy['min_length']} characters.";
        }

        if (strlen($password) > $policy['max_length']) {
            $errors[] = "Password must not exceed {$policy['max_length']} characters.";
        }

        // Complexity checks
        if ($policy['require_uppercase'] && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }

        if ($policy['require_lowercase'] && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }

        if ($policy['require_numbers'] && ! preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        if ($policy['require_symbols'] && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        // Consecutive character check
        if ($policy['max_consecutive_chars'] > 0) {
            $pattern = '/(.)\1{'.$policy['max_consecutive_chars'].',}/';
            if (preg_match($pattern, $password)) {
                $errors[] = "Password cannot have more than {$policy['max_consecutive_chars']} consecutive identical characters.";
            }
        }

        // Common password check
        if ($policy['prevent_common_passwords'] && $this->isCommonPassword($password)) {
            $errors[] = 'This password is too common. Please choose a stronger password.';
        }

        // Username in password check
        if ($policy['prevent_username_in_password'] && $user) {
            $username = strtolower($user->email ?? $user->name ?? '');
            $usernameWithoutDomain = explode('@', $username)[0];

            if (
                stripos($password, $usernameWithoutDomain) !== false &&
                strlen($usernameWithoutDomain) > 2
            ) {
                $errors[] = 'Password cannot contain your username or email.';
            }
        }

        // Password history check
        if ($user && $policy['password_history_count'] > 0) {
            if ($this->wasPasswordUsedRecently($user, $password, $policy['password_history_count'])) {
                $errors[] = "You cannot reuse your last {$policy['password_history_count']} passwords.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculateStrength($password),
        ];
    }

    /**
     * Get Laravel Password validation rule based on policy.
     */
    public function getPasswordRule(?User $user = null): Password
    {
        $policy = $this->getPolicy($user);

        $rule = Password::min($policy['min_length']);

        if ($policy['require_uppercase']) {
            $rule = $rule->mixedCase();
        }

        if ($policy['require_numbers']) {
            $rule = $rule->numbers();
        }

        if ($policy['require_symbols']) {
            $rule = $rule->symbols();
        }

        if ($policy['prevent_common_passwords']) {
            $rule = $rule->uncompromised();
        }

        return $rule;
    }

    /**
     * Check if a user's password has expired.
     */
    public function isPasswordExpired(User $user): bool
    {
        $policy = $this->getPolicy($user);

        if ($policy['password_expiry_days'] <= 0) {
            return false;
        }

        $lastChanged = $user->password_changed_at ?? $user->created_at;

        if (! $lastChanged) {
            return true;
        }

        return $lastChanged->addDays($policy['password_expiry_days'])->isPast();
    }

    /**
     * Get days until password expires.
     *
     * @return int|null Null if no expiration
     */
    public function getDaysUntilExpiry(User $user): ?int
    {
        $policy = $this->getPolicy($user);

        if ($policy['password_expiry_days'] <= 0) {
            return null;
        }

        $lastChanged = $user->password_changed_at ?? $user->created_at;

        if (! $lastChanged) {
            return 0;
        }

        $expiryDate = $lastChanged->addDays($policy['password_expiry_days']);
        $daysLeft = now()->diffInDays($expiryDate, false);

        return max(0, (int) $daysLeft);
    }

    /**
     * Record a password change in the user's history.
     */
    public function recordPasswordChange(User $user, string $hashedPassword): void
    {
        $policy = $this->getPolicy($user);
        $historyCount = $policy['password_history_count'];

        if ($historyCount <= 0) {
            return;
        }

        $history = $user->password_history ?? [];

        // Add new password to history
        array_unshift($history, [
            'hash' => $hashedPassword,
            'changed_at' => now()->toDateTimeString(),
        ]);

        // Keep only the required number of passwords
        $history = array_slice($history, 0, $historyCount);

        $user->update([
            'password_history' => $history,
            'password_changed_at' => now(),
        ]);
    }

    /**
     * Get the password policy for a user (tenant-specific or default).
     */
    public function getPolicy(?User $user = null): array
    {
        // Check for tenant-specific policy
        $tenant = tenant();

        if ($tenant) {
            $cacheKey = "password_policy:tenant:{$tenant->id}";

            return TenantCache::remember($cacheKey, 3600, function () use ($tenant) {
                $tenantPolicy = $tenant->settings['password_policy'] ?? [];

                return array_merge($this->defaultPolicy, $tenantPolicy);
            });
        }

        return $this->defaultPolicy;
    }

    /**
     * Update tenant password policy.
     */
    public function updatePolicy(array $policySettings): void
    {
        $tenant = tenant();

        if (! $tenant) {
            throw new \RuntimeException('Cannot update policy without tenant context.');
        }

        $current = $tenant->settings ?? [];
        $current['password_policy'] = array_merge(
            $this->defaultPolicy,
            $policySettings
        );

        $tenant->update(['settings' => $current]);

        TenantCache::forget("password_policy:tenant:{$tenant->id}");
    }

    /**
     * Calculate password strength score.
     *
     * @return array ['score' => 0-100, 'label' => string]
     */
    public function calculateStrength(string $password): array
    {
        $score = 0;
        $length = strlen($password);

        // Length scoring
        if ($length >= 8) {
            $score += 20;
        }
        if ($length >= 12) {
            $score += 10;
        }
        if ($length >= 16) {
            $score += 10;
        }

        // Character variety scoring
        if (preg_match('/[a-z]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $score += 15;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 15;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 20;
        }

        // Penalties
        if ($this->isCommonPassword($password)) {
            $score -= 40;
        }
        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 10; // Repeated characters
        }
        if (preg_match('/^[0-9]+$/', $password)) {
            $score -= 20; // Numbers only
        }
        if (preg_match('/^[a-zA-Z]+$/', $password)) {
            $score -= 15; // Letters only
        }

        $score = max(0, min(100, $score));

        $label = match (true) {
            $score >= 80 => 'strong',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            $score >= 20 => 'weak',
            default => 'very_weak',
        };

        return [
            'score' => $score,
            'label' => $label,
        ];
    }

    /**
     * Check if password is in the common passwords list.
     */
    protected function isCommonPassword(string $password): bool
    {
        return in_array(strtolower($password), array_map('strtolower', $this->commonPasswords));
    }

    /**
     * Check if password was used recently.
     */
    protected function wasPasswordUsedRecently(User $user, string $password, int $historyCount): bool
    {
        $history = $user->password_history ?? [];

        foreach (array_slice($history, 0, $historyCount) as $entry) {
            if (Hash::check($password, $entry['hash'])) {
                return true;
            }
        }

        // Also check current password
        if (Hash::check($password, $user->password)) {
            return true;
        }

        return false;
    }

    /**
     * Get password requirements as human-readable list.
     */
    public function getRequirementsList(?User $user = null): array
    {
        $policy = $this->getPolicy($user);
        $requirements = [];

        $requirements[] = "Minimum {$policy['min_length']} characters";

        if ($policy['require_uppercase']) {
            $requirements[] = 'At least one uppercase letter (A-Z)';
        }

        if ($policy['require_lowercase']) {
            $requirements[] = 'At least one lowercase letter (a-z)';
        }

        if ($policy['require_numbers']) {
            $requirements[] = 'At least one number (0-9)';
        }

        if ($policy['require_symbols']) {
            $requirements[] = 'At least one special character (!@#$%^&*)';
        }

        if ($policy['prevent_common_passwords']) {
            $requirements[] = 'Cannot be a commonly used password';
        }

        if ($policy['password_history_count'] > 0) {
            $requirements[] = "Cannot match your last {$policy['password_history_count']} passwords";
        }

        return $requirements;
    }
}
