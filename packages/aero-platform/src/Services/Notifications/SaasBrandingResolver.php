<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Notifications;

use Aero\Kernel\Branding\BrandingPayload;
use Aero\Notifications\Contracts\BrandingResolver;
use Aero\Platform\Models\Infra\TenantBranding;
use Aero\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Storage;

/**
 * SaaS-aware notification branding: the FULL white-label chain.
 *
 *   tenant SystemSetting branding → central TenantBranding (platform-managed)
 *   → platform brand (PlatformSetting) → Meridian defaults.
 *
 * Rebinds over CoreBrandingResolver (which only sees the tenant layer) in
 * SaaS mode; standalone keeps the core resolver. Runs in whichever context
 * sends the mail, so tenancy state decides which layers apply.
 */
class SaasBrandingResolver implements BrandingResolver
{
    public function resolve(): array
    {
        $layers = [];

        // 1. Tenant's own branding (only meaningful under tenancy)
        if ($this->inTenantContext()) {
            $layers[] = $this->tenantLayer();
            $layers[] = $this->centralTenantLayer();
        }

        // 2. Platform brand
        $layers[] = $this->platformLayer();

        $branding = BrandingPayload::merge(...array_filter($layers, fn ($l) => $l !== []));

        return [
            'company_name' => $branding['name'],
            'logo_url' => $this->emailSafeUrl($branding['logo_light'] ?? $branding['logo_dark']),
            'primary_color' => $branding['primary_color'],
            'support_email' => $branding['email_from_address'] ?? config('mail.from.address', ''),
            'support_phone' => '',
            'email_from_name' => $branding['email_from_name'] ?? null,
            'email_from_address' => $branding['email_from_address'] ?? null,
        ];
    }

    private function inTenantContext(): bool
    {
        return function_exists('tenancy') && tenancy()->initialized;
    }

    /**
     * Tenant-suffixed asset URLs (/tenancy/assets/…) only serve on a TENANT
     * domain. Queued/CLI renders generate them against app.url (central),
     * which 500s in the recipient's mail client — pin the host to the
     * tenant's primary domain instead.
     */
    private function emailSafeUrl(?string $url): ?string
    {
        if (! $url || ! str_contains($url, '/tenancy/assets/') || ! $this->inTenantContext()) {
            return $url;
        }

        try {
            $domain = tenant()->domains()->where('is_primary', true)->first()
                ?? tenant()->domains()->first();
            if (! $domain) {
                return $url;
            }

            $parts = parse_url($url);
            $scheme = $parts['scheme'] ?? 'https';
            $pathAndQuery = ($parts['path'] ?? '').(isset($parts['query']) ? '?'.$parts['query'] : '');

            return "{$scheme}://{$domain->domain}{$pathAndQuery}";
        } catch (\Throwable) {
            return $url;
        }
    }

    private function tenantLayer(): array
    {
        try {
            $setting = \Aero\Core\Models\SystemSetting::current();
            $layer = $setting->getBrandingPayload();
            $layer['name'] ??= $layer['app_name']
                ?? ($setting->organization['company_name'] ?? null);

            return $layer;
        } catch (\Throwable) {
            return [];
        }
    }

    private function centralTenantLayer(): array
    {
        try {
            $row = TenantBranding::query()->where('tenant_id', tenant('id'))->first();
            if (! $row) {
                return [];
            }

            $disk = Storage::disk('public');
            $url = fn (?string $path) => $path ? $disk->url($path) : null;

            return array_filter([
                'name' => $row->name,
                'logo_light' => $url($row->logo_path),
                'logo_dark' => $url($row->logo_dark_path),
                'logo_icon' => $url($row->logo_icon_path),
                'favicon' => $url($row->favicon_path),
                'primary_color' => $row->primary_color,
                'accent_color' => $row->secondary_color,
                'email_from_name' => $row->email_from_name,
                'email_from_address' => $row->email_from_address,
            ]);
        } catch (\Throwable) {
            return [];
        }
    }

    private function platformLayer(): array
    {
        try {
            $setting = PlatformSetting::current();
            $layer = $setting->getBrandingPayload();
            $layer['name'] ??= $setting->site_name;

            return $layer;
        } catch (\Throwable) {
            return [];
        }
    }
}
