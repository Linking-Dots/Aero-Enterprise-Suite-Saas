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
        $configPath = storage_path('framework/installation_config.json');
        $persisted = [];
        if (file_exists($configPath)) {
            $persisted = json_decode(file_get_contents($configPath), true) ?? [];
        }
        $settings = $persisted['settings'] ?? [];

        if (empty($settings)) {
            $this->warn('No persisted settings found; skipping settings step');
            return ['settings_configured' => 0, 'settings' => []];
        }

        $toSet = [
            'app.name' => $settings['site_name'] ?? $settings['company_name'] ?? null,
            'app.url' => $settings['app_url'] ?? null,
            'app.timezone' => $settings['timezone'] ?? null,
            'app.locale' => $settings['locale'] ?? 'en',
            'mail.from.name' => $settings['mail_from_name'] ?? null,
            'mail.from.address' => $settings['mail_from_address'] ?? null,
        ];

        $saved = 0;
        foreach ($toSet as $key => $value) {
            if ($value === null) {
                continue;
            }
            try {
                $this->setSetting($key, $value);
                $saved++;
            } catch (\Exception $e) {
                $this->warn("Failed to set setting {$key}: ".$e->getMessage());
            }
        }

        $this->log('Platform settings configured from persisted config');

        return [
            'settings_configured' => $saved,
            'settings' => $toSet,
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
