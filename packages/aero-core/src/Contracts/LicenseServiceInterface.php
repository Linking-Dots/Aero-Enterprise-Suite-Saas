<?php

// packages/aero-core/src/Contracts/LicenseServiceInterface.php

namespace Aero\Core\Contracts;

use Aero\Core\Exceptions\LicenseException;

interface LicenseServiceInterface
{
    /**
     * Validate a license key format offline (no network call).
     *
     * @throws LicenseException if format is invalid
     */
    public function validateFormat(string $licenseKey): void;

    /**
     * Activate a license key against the license server.
     * Stores activation result and binds the current domain.
     * Must be called once during installation.
     *
     * @throws LicenseException if activation fails
     */
    public function activate(string $licenseKey, string $productId): void;

    /**
     * Check whether the current installation has a valid license.
     * Uses cached result; re-checks remotely at most once per 24 hours.
     * Returns true in SaaS mode unconditionally.
     */
    public function isValid(): bool;

    /**
     * Get a human-readable status for the license.
     * Returns one of: 'valid', 'grace', 'invalid', 'not_activated', 'saas'
     */
    public function status(): string;

    /**
     * Get remaining grace period seconds (0 if not in grace period).
     */
    public function graceSecondsRemaining(): int;
}
