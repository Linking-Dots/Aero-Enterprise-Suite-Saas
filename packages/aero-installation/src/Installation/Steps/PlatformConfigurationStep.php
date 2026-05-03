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
     * Configure platform settings from UI input (environment variables)
     * Settings are collected from UI and set as environment variables
     */
    protected function seedPlatformSettings(): void
    {
        $now = now();

        // Check if settings already exist
        $existing = DB::table('platform_settings')->count();
        if ($existing > 0) {
            $this->log("Platform settings already exist ({$existing} records), skipping configuration");
            return;
        }

        // Collect settings from UI (environment variables set by UI)
        // Only use columns that exist in the platform_settings table
        $settings = [
            'slug' => 'platform',
            'site_name' => env('SITE_NAME', 'aeos365'),
            'legal_name' => env('COMPANY_NAME', 'Aero Enterprise'),
            'tagline' => env('TAGLINE', null),
            'support_email' => env('SUPPORT_EMAIL', 'support@aero-suite.com'),
            'support_phone' => env('SUPPORT_PHONE', null),
            'marketing_url' => env('MARKETING_URL', null),
            'status_page_url' => env('STATUS_PAGE_URL', null),
            'branding' => json_encode([
                'logo' => env('LOGO_URL', null),
                'favicon' => env('FAVICON_URL', null),
            ]),
            'email_settings' => json_encode([
                'from_name' => env('EMAIL_FROM_NAME', 'Aero Enterprise'),
                'from_address' => env('EMAIL_FROM_ADDRESS', 'noreply@aero-suite.com'),
            ]),
            'legal' => json_encode([
                'terms_url' => env('TERMS_URL', null),
                'privacy_url' => env('PRIVACY_URL', null),
            ]),
            'metadata' => json_encode([
                'time_zone' => env('TIME_ZONE', 'UTC'),
                'trial_days' => (int) env('TRIAL_DAYS', 14),
                'grace_days' => (int) env('GRACE_DAYS', 7),
                'currency' => env('CURRENCY', 'USD'),
                'tax_rate' => (float) env('TAX_RATE', 0),
                'registration_enabled' => filter_var(env('REGISTRATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                'allow_subdomain_registration' => filter_var(env('ALLOW_SUBDOMAIN_REGISTRATION', true), FILTER_VALIDATE_BOOLEAN),
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('platform_settings')->insert($settings);
        $this->log('Platform settings configured from UI input');
    }
}
