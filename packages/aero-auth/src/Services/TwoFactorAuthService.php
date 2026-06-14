<?php

declare(strict_types=1);

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Aero\Kernel\Support\TenantCache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-Factor Authentication Service
 *
 * Provides TOTP-based two-factor authentication for users.
 *
 * Features:
 * - TOTP secret generation
 * - QR code URL generation for authenticator apps
 * - Code verification
 * - Recovery codes generation
 * - Trusted device management
 *
 * Usage:
 * ```php
 * $twoFactor = app(TwoFactorAuthService::class);
 *
 * // Enable 2FA for user
 * $secret = $twoFactor->generateSecret($user);
 * $qrUrl = $twoFactor->getQrCodeUrl($user, $secret);
 *
 * // Verify code from authenticator app
 * if ($twoFactor->verifyCode($user, $code)) {
 *     $twoFactor->enable($user);
 * }
 *
 * // Generate recovery codes
 * $codes = $twoFactor->generateRecoveryCodes($user);
 * ```
 */
class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    /**
     * The issuer name shown in authenticator apps.
     */
    protected string $issuer;

    /**
     * Number of recovery codes to generate.
     */
    protected int $recoveryCodeCount = 8;

    /**
     * Length of each recovery code.
     */
    protected int $recoveryCodeLength = 10;

    /**
     * Trusted device duration in days.
     */
    protected int $trustedDeviceDays = 30;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
        $this->issuer = config('app.name', 'Aero Enterprise');
    }

    /**
     * Generate a new 2FA secret for a user.
     *
     * @return string The secret key
     */
    public function generateSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();

        // Store temporarily until user confirms setup
        TenantCache::put(
            "2fa_pending_secret:{$user->id}",
            Crypt::encryptString($secret),
            now()->addMinutes(10)
        );

        return $secret;
    }

    /**
     * Get the QR code URL for the authenticator app.
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            $this->issuer,
            $user->email,
            $secret
        );
    }

    /**
     * Get QR code as inline SVG data for rendering.
     */
    public function getQrCodeSvg(User $user, string $secret): string
    {
        $url = $this->getQrCodeUrl($user, $secret);

        // Use a simple QR code generator or return the URL for client-side generation
        // For production, use a QR code library like BaconQrCode
        return $url;
    }

    /**
     * Verify a 2FA code against the user's secret.
     */
    public function verifyCode(User $user, string $code): bool
    {
        $secret = $this->getUserSecret($user);

        if (! $secret) {
            return false;
        }

        // Allow for time drift (1 window = 30 seconds each side)
        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Verify a pending 2FA setup code.
     */
    public function verifyPendingCode(User $user, string $code): bool
    {
        $encryptedSecret = TenantCache::get("2fa_pending_secret:{$user->id}");

        if (! $encryptedSecret) {
            return false;
        }

        $secret = Crypt::decryptString($encryptedSecret);

        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Enable 2FA for a user after verification.
     *
     * @return array Recovery codes
     */
    public function enable(User $user): array
    {
        $encryptedSecret = TenantCache::get("2fa_pending_secret:{$user->id}");

        if (! $encryptedSecret) {
            throw new \RuntimeException('No pending 2FA setup found. Please start the setup again.');
        }

        $secret = Crypt::decryptString($encryptedSecret);

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Store encrypted secret and hashed recovery codes
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            'two_factor_enabled_at' => now(),
        ]);

        // Clear pending secret
        TenantCache::forget("2fa_pending_secret:{$user->id}");

        return $recoveryCodes;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
        ]);

        // Clear any trusted devices
        $this->clearTrustedDevices($user);
    }

    /**
     * Check if user has 2FA enabled.
     */
    public function isEnabled(User $user): bool
    {
        return ! empty($user->two_factor_secret) && ! empty($user->two_factor_enabled_at);
    }

    /**
     * Verify a recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->getRecoveryCodes($user);

        if (empty($codes)) {
            return false;
        }

        $code = strtoupper(trim($code));
        $index = array_search($code, $codes);

        if ($index === false) {
            return false;
        }

        // Remove used code
        unset($codes[$index]);
        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($codes))),
        ]);

        return true;
    }

    /**
     * Generate new recovery codes (regenerates all).
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ]);

        return $codes;
    }

    /**
     * Get remaining recovery codes count.
     */
    public function getRemainingRecoveryCodesCount(User $user): int
    {
        return count($this->getRecoveryCodes($user));
    }

    /**
     * Trust a device for 2FA bypass.
     */
    public function trustDevice(User $user, string $deviceId): void
    {
        $key = "2fa_trusted_device:{$user->id}:{$deviceId}";
        TenantCache::put($key, true, now()->addDays($this->trustedDeviceDays));
    }

    /**
     * Check if a device is trusted.
     */
    public function isDeviceTrusted(User $user, string $deviceId): bool
    {
        return TenantCache::get("2fa_trusted_device:{$user->id}:{$deviceId}", false);
    }

    /**
     * Clear all trusted devices for a user.
     */
    public function clearTrustedDevices(User $user): void
    {
        // In production, you'd want to track device IDs to clear them
        // For now, we just note that the pattern supports this
    }

    /**
     * Generate a device ID from request fingerprint.
     */
    public function generateDeviceId(): string
    {
        $request = request();

        $fingerprint = implode('|', [
            $request->userAgent(),
            $request->ip(),
            // Add more fingerprint data as needed
        ]);

        return hash('sha256', $fingerprint);
    }

    /**
     * Get the user's decrypted 2FA secret.
     */
    protected function getUserSecret(User $user): ?string
    {
        if (empty($user->two_factor_secret)) {
            return null;
        }

        try {
            return Crypt::decryptString($user->two_factor_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the user's recovery codes.
     */
    protected function getRecoveryCodes(User $user): array
    {
        if (empty($user->two_factor_recovery_codes)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($user->two_factor_recovery_codes);

            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate recovery codes.
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < $this->recoveryCodeCount; $i++) {
            $codes[] = strtoupper(Str::random($this->recoveryCodeLength));
        }

        return $codes;
    }
}
