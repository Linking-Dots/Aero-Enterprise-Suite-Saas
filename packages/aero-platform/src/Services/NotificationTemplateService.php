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
    protected array $platformBranding = [
        'company_name' => 'aeos365',
        'logo_url' => '/images/platform-logo.png',
        'primary_color' => '#3B82F6',
        'support_email' => 'support@aeroenterprise.com',
        'support_phone' => '+1-800-AERO-365',
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

        $messages = [
            'quota-warning' => "{$companyName}: You're at {$data['percentage']}% of your {$data['quota_type']} quota. Upgrade to avoid service interruption.",
            'quota-critical' => "{$companyName}: URGENT - You've exceeded your {$data['quota_type']} quota. Service may be interrupted in {$data['grace_days']} days.",
            'trial-expiry' => "{$companyName}: Your trial expires in {$data['days_remaining']} days. Subscribe now to continue using all features.",
            'subscription-renewed' => "{$companyName}: Your subscription has been renewed successfully. Thank you!",
            'payment-failed' => "{$companyName}: Payment failed. Please update your payment method to avoid service interruption.",
        ];

        return $messages[$template] ?? '';
    }

    /**
     * Get branding configuration
     */
    protected function getBranding(?Tenant $tenant): array
    {
        if (! $tenant) {
            return $this->platformBranding;
        }

        // Get tenant custom branding from metadata
        $customBranding = $tenant->metadata['branding'] ?? [];

        return array_merge($this->platformBranding, [
            'company_name' => $customBranding['company_name'] ?? $tenant->name,
            'logo_url' => $customBranding['logo_url'] ?? $this->platformBranding['logo_url'],
            'primary_color' => $customBranding['primary_color'] ?? $this->platformBranding['primary_color'],
            'support_email' => $customBranding['support_email'] ?? $tenant->email ?? $this->platformBranding['support_email'],
            'support_phone' => $customBranding['support_phone'] ?? $this->platformBranding['support_phone'],
        ]);
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
