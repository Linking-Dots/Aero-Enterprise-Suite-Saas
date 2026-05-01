<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\DB;

/**
 * License Step
 *
 * Validates and activates license (Standalone mode only)
 * Skipped in SaaS mode
 */
class LicenseStep extends BaseInstallationStep
{
    public function name(): string
    {
        return 'license';
    }

    public function description(): string
    {
        return 'Validate and activate product license (Standalone only)';
    }

    public function order(): int
    {
        return 3;
    }

    public function dependencies(): array
    {
        return ['config', 'database'];
    }

    public function execute(): array
    {
        $mode = env('INSTALLATION_MODE', 'standalone');

        // Skip in SaaS mode
        if ($mode === 'saas') {
            $this->log('License validation skipped (SaaS mode)');

            return [
                'license_status' => 'skipped',
                'reason' => 'SaaS mode detected',
            ];
        }

        $licenseKey = env('LICENSE_KEY');

        if (empty($licenseKey)) {
            $this->log('No license key provided - continuing without validation');

            return [
                'license_status' => 'not_provided',
                'reason' => 'LICENSE_KEY environment variable not set',
            ];
        }

        // Validate license format
        if (! $this->validateLicenseFormat($licenseKey)) {
            throw new \Exception('Invalid license key format');
        }

        // Store license information
        try {
            $this->storeLicenseInfo($licenseKey);

            return [
                'license_status' => 'validated',
                'license_key' => substr($licenseKey, 0, 10).'...',
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to store license information: '.$e->getMessage());
        }
    }

    public function validate(): bool
    {
        $mode = env('INSTALLATION_MODE', 'standalone');

        // Always valid in SaaS mode
        if ($mode === 'saas') {
            return true;
        }

        // In standalone, check if license is stored
        try {
            $stored = DB::table('settings')
                ->where('key', 'license.key')
                ->exists();

            return $stored;

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Validate license key format
     */
    protected function validateLicenseFormat(string $licenseKey): bool
    {
        // Basic format check (adjust based on actual license format)
        $pattern = '/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/';

        return preg_match($pattern, $licenseKey) === 1;
    }

    /**
     * Store license information
     */
    protected function storeLicenseInfo(string $licenseKey): void
    {
        try {
            if (! DB::table('settings')->exists()) {
                return;
            }

            DB::table('settings')->updateOrInsert(
                ['key' => 'license.key'],
                ['value' => $licenseKey]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => 'license.activated_at'],
                ['value' => now()]
            );

        } catch (\Exception) {
            // Settings table doesn't exist
        }
    }

    public function canSkip(): bool
    {
        return true; // Can skip if no license key
    }

    public function isRetriable(): bool
    {
        return true;
    }
}
