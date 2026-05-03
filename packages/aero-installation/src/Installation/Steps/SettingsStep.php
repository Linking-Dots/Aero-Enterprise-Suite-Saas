<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\DB;

/**
 * Settings Step
 *
 * Initializes platform settings and configuration
 */
class SettingsStep extends BaseInstallationStep
{
    public function name(): string
    {
        return 'settings';
    }

    public function description(): string
    {
        return 'Configure platform settings';
    }

    public function order(): int
    {
        return 10;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration', 'admin'];
    }

    public function execute(): array
    {
        $settings = [];

        // Default settings to configure
        $defaultSettings = [
            'app.name' => env('APP_NAME', 'aeos365'),
            'app.url' => env('APP_URL'),
            'app.timezone' => env('APP_TIMEZONE', 'UTC'),
            'app.locale' => env('APP_LOCALE', 'en'),
            'mail.from.name' => env('APP_NAME', 'Aero'),
            'mail.from.address' => env('MAIL_FROM_ADDRESS', 'noreply@aeros.local'),
        ];

        foreach ($defaultSettings as $key => $value) {
            try {
                // Set in settings table if exists
                $this->setSetting($key, $value);
                $settings[$key] = $value;
            } catch (\Exception $e) {
                $this->warn("Failed to set setting {$key}: ".$e->getMessage());
            }
        }

        $this->log('Platform settings configured');

        return [
            'settings_configured' => count($settings),
            'settings' => $settings,
        ];
    }

    public function validate(): bool
    {
        // Allow step to proceed if database connection is working
        // Settings will be created during execution (fresh install)
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Set a setting value
     */
    protected function setSetting(string $key, mixed $value): void
    {
        try {
            if (! DB::table('settings')->exists()) {
                return;
            }

            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );

        } catch (\Exception) {
            // Settings table doesn't exist
        }
    }

    /**
     * Get a setting value
     */
    protected function getSetting(string $key): ?string
    {
        try {
            if (! DB::table('settings')->exists()) {
                return null;
            }

            $setting = DB::table('settings')
                ->where('key', $key)
                ->first();

            return $setting?->value;

        } catch (\Exception) {
            return null;
        }
    }

    public function canSkip(): bool
    {
        return true;
    }
}
