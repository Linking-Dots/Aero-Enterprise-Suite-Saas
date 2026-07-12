<?php

namespace Aero\Platform\Models;

use Aero\Core\Support\TenantCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class PlatformSetting extends CentralModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * Platform settings are stored in the central (landlord) database.
     * Ensure tenant requests do not look for this table in the tenant schema.
     */
    protected $connection = 'central';

    public const DEFAULT_SLUG = 'platform';

    public const MEDIA_LOGO = 'platform_logo';

    public const MEDIA_LOGO_LIGHT = 'platform_logo_light';

    public const MEDIA_LOGO_DARK = 'platform_logo_dark';

    public const MEDIA_SQUARE_LOGO = 'platform_square_logo';

    public const MEDIA_FAVICON = 'platform_favicon';

    public const MEDIA_SOCIAL = 'platform_social_image';

    public const MEDIA_LOGIN_BACKGROUND = 'platform_login_background';

    /**
     * Cache key for maintenance mode status.
     */
    public const CACHE_KEY_MAINTENANCE = 'platform:maintenance_mode';

    /**
     * Cache TTL for maintenance mode status (in seconds).
     */
    public const CACHE_TTL_MAINTENANCE = 60;

    /**
     * HTTP header name for bypass secret.
     */
    public const BYPASS_HEADER = 'X-Maintenance-Bypass';

    /**
     * Hosting mode constants.
     * shared    = cPanel API (Namecheap / shared hosting)
     * dedicated = standard CREATE DATABASE SQL (VPS / cloud)
     */
    public const HOSTING_MODE_SHARED = 'shared';

    public const HOSTING_MODE_DEDICATED = 'dedicated';

    /** Cache key for hosting settings. */
    public const CACHE_KEY_HOSTING = 'platform:hosting_settings';

    protected $fillable = [
        'slug',
        'site_name',
        'legal_name',
        'tagline',
        'support_email',
        'support_phone',
        'marketing_url',
        'status_page_url',
        'branding',
        'metadata',
        'email_settings',
        'sms_settings',
        'legal',
        'integrations',
        'admin_preferences',
        // Maintenance mode fields
        'maintenance_mode',
        'maintenance_message',
        'maintenance_bypass_ips',
        'maintenance_bypass_secret',
        'maintenance_allowed_paths',
        'scheduled_maintenance_at',
        'maintenance_ends_at',
        'maintenance_skip_verification',
        // SEO & Marketing fields
        'seo_settings',
        'analytics_integrations',
        'social_auth_settings',
        'affiliate_settings',
        'newsletter_settings',
        // Infrastructure / Hosting
        'hosting_settings',
        // AI Assistant (Aeon) control plane
        'ai_settings',
    ];

    protected $casts = [
        'branding' => 'array',
        'metadata' => 'array',
        'email_settings' => 'array',
        'sms_settings' => 'array',
        'legal' => 'array',
        'integrations' => 'array',
        'admin_preferences' => 'array',
        // Maintenance mode casts
        'maintenance_mode' => 'boolean',
        'maintenance_bypass_ips' => 'array',
        'maintenance_allowed_paths' => 'array',
        'scheduled_maintenance_at' => 'datetime',
        'maintenance_ends_at' => 'datetime',
        // Infrastructure
        'hosting_settings' => 'array',
        'ai_settings' => 'array',
        'maintenance_skip_verification' => 'boolean',
        // SEO & Marketing casts
        'seo_settings' => 'array',
        'analytics_integrations' => 'array',
        'social_auth_settings' => 'array',
        'affiliate_settings' => 'array',
        'newsletter_settings' => 'array',
    ];

    protected $attributes = [
        'branding' => '[]',
        'metadata' => '[]',
        'email_settings' => '[]',
        'sms_settings' => '[]',
        'legal' => '[]',
        'integrations' => '[]',
        'admin_preferences' => '[]',
        'maintenance_mode' => false,
        'maintenance_bypass_ips' => '[]',
        'maintenance_allowed_paths' => '[]',
        'maintenance_skip_verification' => false,
        // SEO & Marketing defaults
        'seo_settings' => '[]',
        'analytics_integrations' => '[]',
        'social_auth_settings' => '[]',
        'affiliate_settings' => '[]',
        'newsletter_settings' => '[]',
        // Infrastructure — default to dedicated (VPS/MySQL)
        'hosting_settings' => '{"mode":"dedicated","cpanel_host":null,"cpanel_port":2083,"cpanel_username":null,"cpanel_api_token":null,"cpanel_db_user":null}',
        // AI Assistant (Aeon) — sensible defaults; api_key null → falls back to .env
        'ai_settings' => '{"enabled":true,"provider":"gemini","fast_model":"gemini-flash-latest","premium_model":"gemini-2.5-pro","api_key":null,"base_url":null,"token_fuse_per_conversation":8000,"token_fuse_per_user_daily":250000,"max_tool_steps":5}',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function booted(): void
    {
        // Clear caches when settings are updated
        static::saved(function (self $setting) {
            TenantCache::forget(self::CACHE_KEY_MAINTENANCE);
            TenantCache::forget(self::CACHE_KEY_HOSTING);
        });
    }

    public static function current(): self
    {
        return static::firstOrCreate(
            ['slug' => self::DEFAULT_SLUG],
            ['site_name' => config('app.name', 'aeos365')]
        );
    }

    /**
     * Get cached maintenance mode status for high-performance middleware checks.
     *
     * Returns an array with all maintenance-related settings to avoid
     * multiple database queries per request.
     */
    public static function getMaintenanceStatus(): array
    {
        return TenantCache::remember(
            self::CACHE_KEY_MAINTENANCE,
            self::CACHE_TTL_MAINTENANCE,
            function () {
                $setting = static::current();

                return [
                    'enabled' => $setting->maintenance_mode,
                    'message' => $setting->maintenance_message ?? 'The platform is currently undergoing scheduled maintenance. We\'ll be back shortly.',
                    'bypass_ips' => $setting->maintenance_bypass_ips ?? [],
                    'bypass_secret' => $setting->maintenance_bypass_secret,
                    'allowed_paths' => $setting->maintenance_allowed_paths ?? [],
                    'scheduled_at' => $setting->scheduled_maintenance_at?->toIso8601String(),
                    'ends_at' => $setting->maintenance_ends_at?->toIso8601String(),
                ];
            }
        );
    }

    /**
     * Check if global maintenance mode is currently active.
     */
    public static function isMaintenanceModeEnabled(): bool
    {
        return self::getMaintenanceStatus()['enabled'];
    }

    /**
     * Enable global maintenance mode.
     */
    public function enableMaintenanceMode(?string $message = null, ?\DateTimeInterface $endsAt = null): bool
    {
        $updated = $this->update([
            'maintenance_mode' => true,
            'maintenance_message' => $message,
            'maintenance_ends_at' => $endsAt,
        ]);

        TenantCache::forget(self::CACHE_KEY_MAINTENANCE);

        return $updated;
    }

    /**
     * Disable global maintenance mode.
     */
    public function disableMaintenanceMode(): bool
    {
        $updated = $this->update([
            'maintenance_mode' => false,
            'maintenance_ends_at' => null,
        ]);

        TenantCache::forget(self::CACHE_KEY_MAINTENANCE);

        return $updated;
    }

    /**
     * Check if an IP address is in the bypass list.
     */
    public static function isIpBypassed(string $ip): bool
    {
        $status = self::getMaintenanceStatus();
        $bypassIps = $status['bypass_ips'];

        if (empty($bypassIps)) {
            return false;
        }

        return in_array($ip, $bypassIps, true);
    }

    /**
     * Check if a secret matches the bypass secret.
     */
    public static function isSecretValid(?string $secret): bool
    {
        if (empty($secret)) {
            return false;
        }

        $status = self::getMaintenanceStatus();

        return ! empty($status['bypass_secret']) && hash_equals($status['bypass_secret'], $secret);
    }

    /**
     * Check if a path is in the allowed paths during maintenance.
     */
    public static function isPathAllowed(string $path): bool
    {
        $status = self::getMaintenanceStatus();
        $allowedPaths = $status['allowed_paths'];

        if (empty($allowedPaths)) {
            return false;
        }

        $path = '/'.ltrim($path, '/');

        foreach ($allowedPaths as $allowed) {
            $pattern = '/'.ltrim($allowed, '/');

            // Exact match
            if ($path === $pattern) {
                return true;
            }

            // Wildcard match (e.g., /api/health/*)
            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim($pattern, '*');
                if (str_starts_with($path, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_LOGO)->singleFile();
        $this->addMediaCollection(self::MEDIA_LOGO_LIGHT)->singleFile();
        $this->addMediaCollection(self::MEDIA_LOGO_DARK)->singleFile();
        $this->addMediaCollection(self::MEDIA_SQUARE_LOGO)->singleFile();
        $this->addMediaCollection(self::MEDIA_FAVICON)->singleFile();
        $this->addMediaCollection(self::MEDIA_SOCIAL)->singleFile();
        $this->addMediaCollection(self::MEDIA_LOGIN_BACKGROUND)->singleFile();
    }

    public function getBrandingPayload(): array
    {
        $branding = $this->branding ?? [];

        return array_merge([
            'logo' => $this->getFirstMediaUrl(self::MEDIA_LOGO) ?: data_get($branding, 'logo'),
            'logo_light' => $this->getFirstMediaUrl(self::MEDIA_LOGO_LIGHT) ?: data_get($branding, 'logo_light'),
            'logo_dark' => $this->getFirstMediaUrl(self::MEDIA_LOGO_DARK) ?: data_get($branding, 'logo_dark'),
            'square_logo' => $this->getFirstMediaUrl(self::MEDIA_SQUARE_LOGO) ?: data_get($branding, 'square_logo'),
            // Canonical key for the square icon-only mark (square_logo is legacy)
            'logo_icon' => $this->getFirstMediaUrl(self::MEDIA_SQUARE_LOGO) ?: data_get($branding, 'logo_icon', data_get($branding, 'square_logo')),
            'favicon' => $this->getFirstMediaUrl(self::MEDIA_FAVICON) ?: data_get($branding, 'favicon'),
            'social' => $this->getFirstMediaUrl(self::MEDIA_SOCIAL) ?: data_get($branding, 'social'),
            'login_background' => $this->getFirstMediaUrl(self::MEDIA_LOGIN_BACKGROUND) ?: data_get($branding, 'login_background'),
            // Pure override layer: null means "inherit Meridian defaults" —
            // baking colors here used to leak them into every tenant's chain.
            'primary_color' => data_get($branding, 'primary_color'),
            'accent_color' => data_get($branding, 'accent_color'),
        ], $branding);
    }

    // =========================================================================
    // INFRASTRUCTURE / HOSTING HELPERS
    // =========================================================================

    /**
     * Resolve the active hosting mode.
     *
     * Precedence:
     *  1. DB  — platform_settings.hosting_settings.mode
     *  2. ENV — TENANCY_DATABASE_MANAGER=cpanel  → 'shared'
     *  3. Default → 'dedicated'
     */
    public function getHostingMode(): string
    {
        $stored = $this->hosting_settings['mode'] ?? null;

        if ($stored && in_array($stored, [self::HOSTING_MODE_SHARED, self::HOSTING_MODE_DEDICATED], true)) {
            return $stored;
        }

        // Legacy .env fallback
        return env('TENANCY_DATABASE_MANAGER', 'mysql') === 'cpanel'
            ? self::HOSTING_MODE_SHARED
            : self::HOSTING_MODE_DEDICATED;
    }

    /**
     * Shorthand check: is the platform running on shared/cPanel hosting?
     */
    public function isCpanelMode(): bool
    {
        return $this->getHostingMode() === self::HOSTING_MODE_SHARED;
    }

    /**
     * Return hosting settings with the cPanel API token decrypted.
     * Safe to pass to internal provisioning — never expose via HTTP.
     */
    public function getHostingSettingsDecrypted(): array
    {
        $settings = $this->hosting_settings ?? [];

        if (! empty($settings['cpanel_api_token'])) {
            try {
                $settings['cpanel_api_token'] = Crypt::decryptString($settings['cpanel_api_token']);
            } catch (Throwable $e) {
                // Token stored in plain-text (legacy / manually set via .env migration)
                // Leave as-is
            }
        }

        return $settings;
    }

    /**
     * Return hosting settings safe for the admin UI (token masked).
     */
    public function getSanitizedHostingSettings(): array
    {
        $settings = $this->hosting_settings ?? [];

        if (! empty($settings['cpanel_api_token'])) {
            $settings['cpanel_api_token_set'] = true;
            $settings['cpanel_api_token'] = null;
        }

        // Expose resolved active mode so the UI always knows what is in effect
        $settings['mode'] = $settings['mode'] ?? self::HOSTING_MODE_DEDICATED;
        $settings['resolved_mode'] = $this->getHostingMode();
        $settings['env_override'] = env('TENANCY_DATABASE_MANAGER') !== null;

        return $settings;
    }

    /**
     * Default AI (Aeon) control-plane settings. Any missing key resolves here.
     *
     * @return array<string, mixed>
     */
    public static function aiSettingsDefaults(): array
    {
        return [
            'enabled' => true,
            'provider' => 'gemini',
            'fast_model' => 'gemini-flash-latest',
            'premium_model' => 'gemini-2.5-pro',
            'api_key' => null,
            'base_url' => null,
            'token_fuse_per_conversation' => 8000,
            'token_fuse_per_user_daily' => 250000,
            'max_tool_steps' => 5,
        ];
    }

    /**
     * Resolved AI settings for RUNTIME use (the assistant reads this): defaults
     * merged, API key decrypted, and empty key/models falling back to the .env
     * config so existing installs keep working before anything is set in the UI.
     *
     * @return array<string, mixed>
     */
    public function getAiSettingsResolved(): array
    {
        $settings = array_merge(self::aiSettingsDefaults(), (array) ($this->ai_settings ?? []));

        if (! empty($settings['api_key'])) {
            try {
                $settings['api_key'] = Crypt::decryptString($settings['api_key']);
            } catch (Throwable $e) {
                // stored plain (legacy / manual) — leave as-is
            }
        } else {
            // Fall back to the provider's env key so nothing breaks pre-config.
            $settings['api_key'] = $settings['provider'] === 'openai'
                ? (config('aeon.providers.openai.api_key') ?: null)
                : (config('aeon.providers.gemini.api_key') ?: null);
        }

        return $settings;
    }

    /**
     * AI settings safe for the admin UI: key masked, only whether it is set.
     *
     * @return array<string, mixed>
     */
    public function getSanitizedAiSettings(): array
    {
        $settings = array_merge(self::aiSettingsDefaults(), (array) ($this->ai_settings ?? []));
        $settings['api_key_set'] = ! empty($settings['api_key']);
        $settings['api_key'] = null;

        return $settings;
    }

    /**
     * Persist AI settings from an admin form. The API key is encrypted; an empty
     * key is left untouched (so saving the form doesn't wipe an existing key).
     *
     * @param  array<string, mixed>  $input
     */
    public function saveAiSettings(array $input): void
    {
        $current = array_merge(self::aiSettingsDefaults(), (array) ($this->ai_settings ?? []));

        $next = array_merge($current, array_intersect_key($input, self::aiSettingsDefaults()));

        // Encrypt a newly-provided key; blank input keeps the stored one.
        if (array_key_exists('api_key', $input) && filled($input['api_key'])) {
            $next['api_key'] = Crypt::encryptString((string) $input['api_key']);
        } else {
            $next['api_key'] = $current['api_key'];
        }

        $this->ai_settings = $next;
        $this->save();
    }

    public function getSanitizedEmailSettings(): array
    {
        $email = $this->email_settings ?? [];

        if (! empty($email['password'])) {
            $email['password_set'] = true;
            unset($email['password']);
        }

        return $email;
    }

    public function getEmailPassword(): ?string
    {
        $value = data_get($this->email_settings, 'password');

        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('web')
            ->width(512)
            ->optimize()
            ->nonQueued();
    }

    // =========================================================================
    // SEO & MARKETING HELPERS
    // =========================================================================

    /**
     * Get SEO settings with defaults.
     */
    public function getSeoSettings(): array
    {
        $defaults = [
            'default_meta_title' => $this->site_name,
            'default_meta_description' => $this->tagline,
            'default_og_image' => $this->getFirstMediaUrl(self::MEDIA_SOCIAL),
            'google_analytics_id' => null,
            'google_tag_manager_id' => null,
            'google_search_console_verification' => null,
            'bing_webmaster_verification' => null,
            'facebook_pixel_id' => null,
            'sitemap_enabled' => true,
            'robots_txt' => "User-agent: *\nAllow: /",
            'canonical_domain' => config('app.url'),
            'twitter_handle' => null,
            'facebook_app_id' => null,
        ];

        return array_merge($defaults, $this->seo_settings ?? []);
    }

    /**
     * Get analytics integrations with defaults.
     */
    public function getAnalyticsIntegrations(): array
    {
        $defaults = [
            'google_analytics' => [
                'enabled' => false,
                'tracking_id' => null,
                'track_events' => true,
                'anonymize_ip' => true,
            ],
            'google_tag_manager' => [
                'enabled' => false,
                'container_id' => null,
            ],
            'facebook_pixel' => [
                'enabled' => false,
                'pixel_id' => null,
                'track_page_views' => true,
            ],
            'hotjar' => [
                'enabled' => false,
                'site_id' => null,
            ],
            'mixpanel' => [
                'enabled' => false,
                'token' => null,
            ],
        ];

        return array_merge($defaults, $this->analytics_integrations ?? []);
    }

    /**
     * Get social auth settings with defaults.
     */
    public function getSocialAuthSettings(): array
    {
        $defaults = [
            'enabled' => false,
            'providers' => [
                'google' => [
                    'enabled' => false,
                    'client_id' => null,
                    'client_secret' => null,
                ],
                'linkedin' => [
                    'enabled' => false,
                    'client_id' => null,
                    'client_secret' => null,
                ],
                'facebook' => [
                    'enabled' => false,
                    'client_id' => null,
                    'client_secret' => null,
                ],
                'github' => [
                    'enabled' => false,
                    'client_id' => null,
                    'client_secret' => null,
                ],
            ],
        ];

        return array_merge($defaults, $this->social_auth_settings ?? []);
    }

    /**
     * Get affiliate settings with defaults.
     */
    public function getAffiliateSettings(): array
    {
        $defaults = [
            'enabled' => false,
            'default_commission_rate' => 10.00,
            'default_commission_type' => 'percentage',
            'cookie_days' => 30,
            'minimum_payout' => 50.00,
            'auto_approve_affiliates' => false,
            'require_approval_for_commission' => true,
            'payout_methods' => ['paypal', 'bank_transfer'],
            'terms_and_conditions' => null,
        ];

        return array_merge($defaults, $this->affiliate_settings ?? []);
    }

    /**
     * Get newsletter settings with defaults.
     */
    public function getNewsletterSettings(): array
    {
        $defaults = [
            'enabled' => true,
            'require_confirmation' => true,
            'welcome_email_enabled' => true,
            'welcome_email_subject' => 'Welcome to our newsletter!',
            'unsubscribe_confirmation' => true,
            'default_preferences' => ['product_updates', 'company_news'],
            'mailchimp_api_key' => null,
            'mailchimp_list_id' => null,
            'sendinblue_api_key' => null,
        ];

        return array_merge($defaults, $this->newsletter_settings ?? []);
    }

    /**
     * Generate tracking scripts for head section.
     */
    public function getHeadTrackingScripts(): string
    {
        $scripts = '';
        $analytics = $this->getAnalyticsIntegrations();

        // Google Tag Manager (head)
        if ($analytics['google_tag_manager']['enabled'] && $analytics['google_tag_manager']['container_id']) {
            $gtmId = $analytics['google_tag_manager']['container_id'];
            $scripts .= "<!-- Google Tag Manager -->\n";
            $scripts .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$gtmId}');</script>\n";
            $scripts .= "<!-- End Google Tag Manager -->\n";
        }

        // Google Analytics 4
        if ($analytics['google_analytics']['enabled'] && $analytics['google_analytics']['tracking_id']) {
            $gaId = $analytics['google_analytics']['tracking_id'];
            $scripts .= "<!-- Google Analytics -->\n";
            $scripts .= "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$gaId}\"></script>\n";
            $scripts .= "<script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', '{$gaId}');</script>\n";
            $scripts .= "<!-- End Google Analytics -->\n";
        }

        // Facebook Pixel
        if ($analytics['facebook_pixel']['enabled'] && $analytics['facebook_pixel']['pixel_id']) {
            $pixelId = $analytics['facebook_pixel']['pixel_id'];
            $scripts .= "<!-- Facebook Pixel -->\n";
            $scripts .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{$pixelId}');fbq('track','PageView');</script>\n";
            $scripts .= "<!-- End Facebook Pixel -->\n";
        }

        // Hotjar
        if ($analytics['hotjar']['enabled'] && $analytics['hotjar']['site_id']) {
            $hjId = $analytics['hotjar']['site_id'];
            $scripts .= "<!-- Hotjar -->\n";
            $scripts .= "<script>(function(h,o,t,j,a,r){h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};h._hjSettings={hjid:{$hjId},hjsv:6};a=o.getElementsByTagName('head')[0];r=o.createElement('script');r.async=1;r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;a.appendChild(r);})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');</script>\n";
            $scripts .= "<!-- End Hotjar -->\n";
        }

        return $scripts;
    }

    /**
     * Generate tracking scripts for body section (noscript tags).
     */
    public function getBodyTrackingScripts(): string
    {
        $scripts = '';
        $analytics = $this->getAnalyticsIntegrations();

        // Google Tag Manager (noscript)
        if ($analytics['google_tag_manager']['enabled'] && $analytics['google_tag_manager']['container_id']) {
            $gtmId = $analytics['google_tag_manager']['container_id'];
            $scripts .= "<!-- Google Tag Manager (noscript) -->\n";
            $scripts .= "<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$gtmId}\" height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n";
            $scripts .= "<!-- End Google Tag Manager (noscript) -->\n";
        }

        // Facebook Pixel (noscript)
        if ($analytics['facebook_pixel']['enabled'] && $analytics['facebook_pixel']['pixel_id']) {
            $pixelId = $analytics['facebook_pixel']['pixel_id'];
            $scripts .= "<!-- Facebook Pixel (noscript) -->\n";
            $scripts .= "<noscript><img height=\"1\" width=\"1\" style=\"display:none\" src=\"https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1\"/></noscript>\n";
            $scripts .= "<!-- End Facebook Pixel (noscript) -->\n";
        }

        return $scripts;
    }

    /**
     * Get default SEO meta tags for platform pages.
     */
    public function getDefaultSeoMeta(): array
    {
        $seo = $this->getSeoSettings();
        $branding = $this->getBrandingPayload();

        return [
            'title' => $seo['default_meta_title'] ?? $this->site_name,
            'description' => $seo['default_meta_description'] ?? $this->tagline,
            'canonical' => $seo['canonical_domain'] ?? config('app.url'),
            'robots' => 'index, follow',
            'og' => [
                'title' => $seo['default_meta_title'] ?? $this->site_name,
                'description' => $seo['default_meta_description'] ?? $this->tagline,
                'image' => $seo['default_og_image'] ?? $branding['social'] ?? null,
                'type' => 'website',
                'site_name' => $this->site_name,
                'url' => config('app.url'),
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $seo['default_meta_title'] ?? $this->site_name,
                'description' => $seo['default_meta_description'] ?? $this->tagline,
                'image' => $seo['default_og_image'] ?? $branding['social'] ?? null,
                'site' => $seo['twitter_handle'] ?? null,
            ],
        ];
    }

    /**
     * Check if social auth is enabled for a provider.
     */
    public function isSocialAuthEnabled(string $provider): bool
    {
        $settings = $this->getSocialAuthSettings();

        if (! $settings['enabled']) {
            return false;
        }

        return $settings['providers'][$provider]['enabled'] ?? false;
    }

    /**
     * Get enabled social auth providers.
     */
    public function getEnabledSocialAuthProviders(): array
    {
        $settings = $this->getSocialAuthSettings();

        if (! $settings['enabled']) {
            return [];
        }

        return array_filter(
            array_keys($settings['providers']),
            fn ($provider) => $settings['providers'][$provider]['enabled'] ?? false
        );
    }
}
