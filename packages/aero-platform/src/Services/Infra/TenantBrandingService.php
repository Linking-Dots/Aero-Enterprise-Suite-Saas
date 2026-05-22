<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Infra\TenantBranding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantBrandingService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function getForTenant(string $tenantId): TenantBranding
    {
        return TenantBranding::firstOrCreate(
            ['tenant_id' => $tenantId],
            ['primary_color' => '#3B82F6', 'secondary_color' => '#1E40AF']
        );
    }

    public function update(TenantBranding $branding, array $data): TenantBranding
    {
        return DB::transaction(function () use ($branding, $data): TenantBranding {
            $branding->update($data);

            $this->audit->log(
                event: AuditEventType::TENANT_BRANDING_UPDATED->value,
                action: 'update',
                subject: $branding,
                description: "Branding updated for tenant {$branding->tenant_id}"
            );

            return $branding->refresh();
        });
    }

    public function uploadLogo(TenantBranding $branding, UploadedFile $file, string $type = 'logo'): TenantBranding
    {
        return DB::transaction(function () use ($branding, $file, $type): TenantBranding {
            $path = $file->store("tenants/{$branding->tenant_id}/branding", 'public');

            $field = $type === 'favicon' ? 'favicon_path' : 'logo_path';
            $branding->update([$field => $path]);

            $this->audit->log(
                event: AuditEventType::TENANT_BRANDING_UPDATED->value,
                action: 'upload',
                subject: $branding,
                description: "Branding {$type} uploaded for tenant {$branding->tenant_id}"
            );

            return $branding->refresh();
        });
    }

    public function updateCustomCss(TenantBranding $branding, string $css): TenantBranding
    {
        return DB::transaction(function () use ($branding, $css): TenantBranding {
            $path = "tenants/{$branding->tenant_id}/branding/custom.css";
            Storage::disk('public')->put($path, $css);

            $branding->update(['custom_css_path' => $path]);

            $this->audit->log(
                event: AuditEventType::TENANT_CSS_UPDATED->value,
                action: 'update',
                subject: $branding,
                description: "Custom CSS updated for tenant {$branding->tenant_id}"
            );

            return $branding->refresh();
        });
    }

    public function configureDkim(TenantBranding $branding, string $selector, string $privateKey): TenantBranding
    {
        return DB::transaction(function () use ($branding, $selector, $privateKey): TenantBranding {
            $branding->update([
                'dkim_selector' => $selector,
                'dkim_private_key' => $privateKey,
            ]);

            $this->audit->log(
                event: AuditEventType::DKIM_CONFIGURED->value,
                action: 'configure',
                subject: $branding,
                description: "DKIM configured (selector: {$selector}) for tenant {$branding->tenant_id}"
            );

            return $branding->refresh();
        });
    }

    public function verifyDkim(TenantBranding $branding): bool
    {
        if (! $branding->dkim_selector || ! $branding->email_from_address) {
            return false;
        }

        $domain = substr($branding->email_from_address, strpos($branding->email_from_address, '@') + 1);
        $dnsName = "{$branding->dkim_selector}._domainkey.{$domain}";

        $records = dns_get_record($dnsName, DNS_TXT);
        $verified = ! empty($records);

        if ($verified) {
            $this->audit->log(
                event: AuditEventType::DKIM_VERIFIED->value,
                action: 'verify',
                subject: $branding,
                description: "DKIM verified for tenant {$branding->tenant_id}"
            );
        }

        return $verified;
    }
}
