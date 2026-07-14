<?php

declare(strict_types=1);

namespace Aero\Core\Services\Notifications;

use Aero\Core\Models\SystemSetting;
use Aero\Kernel\Branding\BrandingPayload;
use Aero\Notifications\Contracts\BrandingResolver;
use Illuminate\Support\Facades\Log;

/**
 * Resolves notification/email branding through the white-label chain:
 * workspace branding → Meridian defaults. Runs in whichever context sends
 * the mail (tenant or standalone) so SystemSetting::current() is already
 * the right scope.
 */
class CoreBrandingResolver implements BrandingResolver
{
    public function resolve(): array
    {
        $branding = BrandingPayload::defaults();
        $organization = [];
        $mail = [];

        try {
            $setting = SystemSetting::current();
            $layer = $setting->getBrandingPayload();
            $organization = $setting->organization ?? [];
            $mail = $setting->email_settings ?? [];

            $layer['name'] ??= $layer['app_name']
                ?? ($organization['company_name'] ?? null);

            $branding = BrandingPayload::merge($layer);
        } catch (\Throwable $e) {
            Log::debug('CoreBrandingResolver: no system settings, using Meridian defaults');
        }

        return [
            'company_name' => $branding['name'],
            'logo_url' => $branding['logo_light'] ?? $branding['logo_dark'],
            'primary_color' => $branding['primary_color'],
            'support_email' => $branding['email_from_address']
                ?? $mail['from_address']
                ?? ($organization['contact_email'] ?? config('mail.from.address', '')),
            'support_phone' => $organization['contact_phone'] ?? '',
            // Sender identity from Brand Studio — null means "not overridden";
            // the mail context resolver falls back to email settings / env.
            'email_from_name' => $branding['email_from_name'] ?? null,
            'email_from_address' => $branding['email_from_address'] ?? null,
        ];
    }
}
