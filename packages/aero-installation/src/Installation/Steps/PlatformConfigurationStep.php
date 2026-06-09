<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Configuration Step
 *
 * Configures SaaS platform settings
 * Only runs in SaaS mode
 */
class PlatformConfigurationStep extends BaseInstallationStep
{
    protected string $mode;

    public function __construct(string $mode = 'standalone')
    {
        $this->mode = $mode;
    }

    public function name(): string
    {
        return 'platform_configuration';
    }

    public function description(): string
    {
        return 'Configure SaaS platform settings';
    }

    public function order(): int
    {
        return 7;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration'];
    }

    public function execute(): array
    {
        $this->log('Starting platform configuration');

        // Skip in standalone mode
        if ($this->mode !== 'saas') {
            $this->log('Skipping platform configuration in standalone mode');
            return [
                'status' => 'skipped',
                'reason' => 'Standalone mode',
            ];
        }

        // Check if platform_settings table exists
        if (! Schema::hasTable('platform_settings')) {
            $this->warn('Platform settings table does not exist, skipping configuration');
            return [
                'status' => 'skipped',
                'reason' => 'Platform settings table does not exist',
            ];
        }

        // Seed default platform settings
        $this->seedPlatformSettings();

        $this->log('Platform configuration completed');

        return [
            'status' => 'success',
            'settings_configured' => true,
        ];
    }

    public function validate(): bool
    {
        // Allow step to proceed if database connection is working
        // In SaaS mode, platform_settings table may not have records yet (fresh install)
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function canSkip(): bool
    {
        // Skip in standalone mode
        return $this->mode !== 'saas';
    }

    public function isRetriable(): bool
    {
        return true;
    }

    /**
      * Configure platform settings from persisted installation config
      */
     protected function seedPlatformSettings(): void
     {
         $now = now();

         $configPath = storage_path('framework/installation_config.json');
         $persisted = [];
         if (file_exists($configPath)) {
             $persisted = json_decode(file_get_contents($configPath), true) ?? [];
         }
         $settings = $persisted['settings'] ?? [];

         if (empty($settings)) {
             $this->warn('No persisted settings found; platform configuration skipped');
             return;
         }

         $existing = DB::table('platform_settings')->count();
         if ($existing > 0) {
             $this->log("Platform settings already exist ({$existing} records), updating from persisted config");
         }

         $record = [
             'slug' => 'platform',
             'site_name' => $settings['site_name'] ?? null,
             'legal_name' => $settings['company_name'] ?? null,
             'support_email' => $settings['support_email'] ?? null,
             'support_phone' => $settings['support_phone'] ?? null,
             'updated_at' => $now,
         ];

         if (! empty($settings['tagline'])) {
             $record['tagline'] = $settings['tagline'];
         }
         if (! empty($settings['app_url'])) {
             $record['marketing_url'] = $settings['app_url'];
         }

         $branding = [];
         if (! empty($settings['logo_url'])) {
             $branding['logo'] = $settings['logo_url'];
         }
         if (! empty($settings['favicon_url'])) {
             $branding['favicon'] = $settings['favicon_url'];
         }
         if (! empty($branding)) {
             $record['branding'] = json_encode($branding);
         }

         $emailSettings = [];
         if (! empty($settings['mail_from_name'])) {
             $emailSettings['from_name'] = $settings['mail_from_name'];
         }
         if (! empty($settings['mail_from_address'])) {
             $emailSettings['from_address'] = $settings['mail_from_address'];
         }
         if (! empty($emailSettings)) {
             $record['email_settings'] = json_encode($emailSettings);
         }

         $legal = [];
         if (! empty($settings['terms_url'])) {
             $legal['terms_url'] = $settings['terms_url'];
         }
         if (! empty($settings['privacy_url'])) {
             $legal['privacy_url'] = $settings['privacy_url'];
         }
         if (! empty($legal)) {
             $record['legal'] = json_encode($legal);
         }

         $metadata = [];
         if (! empty($settings['timezone'])) {
             $metadata['time_zone'] = $settings['timezone'];
         }
         if (isset($settings['trial_days'])) {
             $metadata['trial_days'] = (int) $settings['trial_days'];
         }
         if (isset($settings['grace_days'])) {
             $metadata['grace_days'] = (int) $settings['grace_days'];
         }
         if (! empty($settings['currency'])) {
             $metadata['currency'] = $settings['currency'];
         }
         if (isset($settings['tax_rate'])) {
             $metadata['tax_rate'] = (float) $settings['tax_rate'];
         }
         if (! empty($metadata)) {
             $record['metadata'] = json_encode($metadata);
         }

         if ($existing > 0) {
             DB::table('platform_settings')->where('slug', 'platform')->update($record);
         } else {
             $record['created_at'] = $now;
             DB::table('platform_settings')->insert($record);
         }

         $this->log('Platform settings configured from persisted installation config');
     }
}
