<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\View;

/**
 * White-Label Notification Template Service
 *
 * Renders notifications with per-tenant branding or platform branding.
 * Supports email templates, SMS messages, and in-app notifications.
 */
class NotificationTemplateService
{
    /** Last-resort floor only — the real values resolve through the white-label chain. */
    protected array $platformBranding = [
        'company_name' => 'aeos365',
        'logo_url' => null,
        'primary_color' => '#0C2742',
        'support_email' => null,
        'support_phone' => null,
    ];

    /**
     * Render email notification with branding
     *
     * @param  string  $template  Template name (e.g., 'quota-warning', 'trial-expiry')
     * @param  array  $data  Template variables
     * @param  Tenant|null  $tenant  Tenant for white-label branding, null for platform branding
     * @return array ['subject' => string, 'html' => string, 'text' => string]
     */
    public function renderEmail(string $template, array $data, ?Tenant $tenant = null): array
    {
        $branding = $this->getBranding($tenant);
        $mergedData = array_merge($data, ['branding' => $branding]);

        $templatePath = "emails.notifications.{$template}";

        return [
            'subject' => $this->getEmailSubject($template, $mergedData),
            'html' => View::make($templatePath, $mergedData)->render(),
            'text' => $this->generatePlainText($templatePath, $mergedData),
            'branding' => $branding,
        ];
    }

    /**
     * Render SMS message
     *
     * @return string SMS message (max 160 characters)
     */
    public function renderSms(string $template, array $data, ?Tenant $tenant = null): string
    {
        $branding = $this->getBranding($tenant);
        $companyName = $branding['company_name'];

        // match — only the selected template's data keys are ever touched
        return match ($template) {
            'quota-warning' => "{$companyName}: You're at {$data['percentage']}% of your {$data['quota_type']} quota. Upgrade to avoid service interruption.",
            'quota-critical' => "{$companyName}: URGENT - You've exceeded your {$data['quota_type']} quota. Service may be interrupted in {$data['grace_days']} days.",
            'trial-expiry' => "{$companyName}: Your trial expires in {$data['days_remaining']} days. Subscribe now to continue using all features.",
            'subscription-renewed' => "{$companyName}: Your subscription has been renewed successfully. Thank you!",
            'payment-failed' => "{$companyName}: Payment failed. Please update your payment method to avoid service interruption.",
            default => '',
        };
    }

    /**
     * Get branding configuration
     */
    protected function getBranding(?Tenant $tenant): array
    {
        $platform = $this->platformLayer();

        if (! $tenant) {
            return $platform;
        }

        // Central per-tenant white-label layer (managed from /white-label),
        // then legacy tenant metadata, then the platform brand.
        $central = $this->centralTenantLayer($tenant);
        $customBranding = $tenant->metadata['branding'] ?? [];

        return [
            'company_name' => $central['company_name'] ?? $customBranding['company_name'] ?? $tenant->name ?? $platform['company_name'],
            'logo_url' => $central['logo_url'] ?? $customBranding['logo_url'] ?? $platform['logo_url'],
            'primary_color' => $central['primary_color'] ?? $customBranding['primary_color'] ?? $platform['primary_color'],
            'support_email' => $central['support_email'] ?? $customBranding['support_email'] ?? $tenant->email ?? $platform['support_email'],
            'support_phone' => $customBranding['support_phone'] ?? $tenant->phone ?? $platform['support_phone'],
        ];
    }

    /** Platform brand through the chain (PlatformSetting → Meridian floor). */
    protected function platformLayer(): array
    {
        try {
            $setting = \Aero\Platform\Models\PlatformSetting::current();
            $layer = $setting->getBrandingPayload();

            return [
                'company_name' => $layer['name'] ?? $setting->site_name ?? $this->platformBranding['company_name'],
                'logo_url' => $layer['logo_light'] ?? $layer['logo_dark'] ?? $this->platformBranding['logo_url'],
                'primary_color' => $layer['primary_color'] ?? $this->platformBranding['primary_color'],
                'support_email' => $layer['email_from_address'] ?? config('mail.from.address') ?? $this->platformBranding['support_email'],
                'support_phone' => $this->platformBranding['support_phone'],
            ];
        } catch (\Throwable) {
            return $this->platformBranding;
        }
    }

    /** The /white-label console's central per-tenant overrides (null = inherit). */
    protected function centralTenantLayer(Tenant $tenant): array
    {
        try {
            $row = \Aero\Platform\Models\Infra\TenantBranding::query()
                ->where('tenant_id', $tenant->id)
                ->first();
            if (! $row) {
                return [];
            }

            $disk = \Illuminate\Support\Facades\Storage::disk('public');

            return array_filter([
                'company_name' => $row->name,
                'logo_url' => $row->logo_path ? $disk->url($row->logo_path) : null,
                'primary_color' => $row->primary_color,
                'support_email' => $row->email_from_address,
            ]);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get email subject based on template
     */
    protected function getEmailSubject(string $template, array $data): string
    {
        $companyName = $data['branding']['company_name'];

        $subjects = [
            'quota-warning' => "{$companyName} - Quota Usage Warning",
            'quota-critical' => "{$companyName} - URGENT: Quota Limit Reached",
            'trial-expiry' => "{$companyName} - Your Trial is Ending Soon",
            'subscription-renewed' => "{$companyName} - Subscription Renewed",
            'payment-failed' => "{$companyName} - Payment Failed",
            'subscription-cancelled' => "{$companyName} - Subscription Cancelled",
            'plan-upgraded' => "{$companyName} - Plan Upgraded Successfully",
            'plan-downgraded' => "{$companyName} - Plan Changed",
        ];

        return $subjects[$template] ?? "{$companyName} - Notification";
    }

    /**
     * Generate plain text version from HTML template
     */
    protected function generatePlainText(string $templatePath, array $data): string
    {
        // Simple HTML to text conversion
        $html = View::make($templatePath, $data)->render();
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Get available notification templates
     */
    public function getAvailableTemplates(): array
    {
        return [
            'quota-warning' => 'Quota Usage Warning (80-90%)',
            'quota-critical' => 'Critical Quota Alert (90%+)',
            'trial-expiry' => 'Trial Expiration Reminder',
            'subscription-renewed' => 'Subscription Renewal Confirmation',
            'payment-failed' => 'Payment Failure Alert',
            'subscription-cancelled' => 'Subscription Cancellation',
            'plan-upgraded' => 'Plan Upgrade Confirmation',
            'plan-downgraded' => 'Plan Downgrade Notice',
        ];
    }

    /**
     * Render in-app notification
     *
     * @return array ['title' => string, 'body' => string, 'type' => string]
     */
    public function renderInApp(string $template, array $data, ?Tenant $tenant = null): array
    {
        $branding = $this->getBranding($tenant);

        $notifications = [
            'quota-warning' => [
                'title' => 'Quota Usage Warning',
                'body' => "You're using {$data['percentage']}% of your {$data['quota_type']} quota. Consider upgrading your plan.",
                'type' => 'warning',
            ],
            'quota-critical' => [
                'title' => 'Quota Limit Reached',
                'body' => "You've exceeded your {$data['quota_type']} quota. Please upgrade to avoid service interruption.",
                'type' => 'error',
            ],
            'trial-expiry' => [
                'title' => 'Trial Ending Soon',
                'body' => "Your trial expires in {$data['days_remaining']} days. Subscribe to continue.",
                'type' => 'info',
            ],
        ];

        return $notifications[$template] ?? [
            'title' => 'Notification',
            'body' => 'You have a new notification',
            'type' => 'info',
        ];
    }

    /**
     * Set platform branding
     */
    public function setPlatformBranding(array $branding): self
    {
        $this->platformBranding = array_merge($this->platformBranding, $branding);

        return $this;
    }
}
