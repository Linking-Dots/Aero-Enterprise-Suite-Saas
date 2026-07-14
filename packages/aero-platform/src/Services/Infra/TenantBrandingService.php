<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Kernel\Branding\BrandingPayload;
use Aero\Platform\Models\Infra\TenantBranding;
use Aero\Platform\Models\Infra\TenantCustomDomain;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantBrandingService
{
    /** BrandStudio asset key → tenant_brandings column (canonical 5-slot taxonomy) */
    public const ASSET_COLUMNS = [
        'logo_light' => 'logo_path',
        'logo_dark' => 'logo_dark_path',
        'logo_icon' => 'logo_icon_path',
        'favicon' => 'favicon_path',
        'login_background' => 'login_background_path',
    ];

    /** BrandStudio scalar key → tenant_brandings column */
    public const SCALAR_COLUMNS = [
        'name' => 'name',
        'primary_color' => 'primary_color',
        'accent_color' => 'secondary_color',
        'email_from_name' => 'email_from_name',
        'email_from_address' => 'email_from_address',
    ];

    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function getForTenant(string $tenantId): TenantBranding
    {
        return TenantBranding::firstOrCreate(['tenant_id' => $tenantId]);
    }

    // -------------------------------------------------------------------------
    // BrandStudio contract (platform-managed per-tenant layer)
    // -------------------------------------------------------------------------

    /**
     * Payload for the shared <BrandStudio>: this row's overrides + the chain
     * it inherits from (platform brand → Meridian).
     */
    public function studioPayload(TenantBranding $branding): array
    {
        $overrides = $this->overridesLayer($branding);
        $platform = $this->platformLayer();

        return [
            'overrides' => $overrides,
            'resolved' => BrandingPayload::merge($overrides, $platform),
            'defaults' => BrandingPayload::merge($platform),
            'entitled' => true, // platform admins always edit on the tenant's behalf
            'customized' => BrandingPayload::isCustomized($overrides),
        ];
    }

    /**
     * Apply a BrandStudio save (scalars + uploads + removals) to the central
     * per-tenant branding row.
     *
     * @param array<string, mixed> $scalars validated scalar inputs (missing key = untouched)
     * @param array<string, UploadedFile> $files uploaded asset files by BrandStudio key
     * @param array<string> $removals BrandStudio keys whose assets should be dropped
     */
    public function updateFromStudio(TenantBranding $branding, array $scalars, array $files, array $removals): TenantBranding
    {
        return DB::transaction(function () use ($branding, $scalars, $files, $removals): TenantBranding {
            $updates = [];

            foreach (self::SCALAR_COLUMNS as $key => $column) {
                if (! array_key_exists($key, $scalars)) {
                    continue;
                }
                $value = $scalars[$key];
                $updates[$column] = ($value === '' ? null : $value);
            }

            // Removals first so an upload in the same save wins over a remove
            foreach ($removals as $key) {
                $column = self::ASSET_COLUMNS[$key] ?? null;
                if ($column && $branding->{$column}) {
                    Storage::disk('public')->delete($branding->{$column});
                    $updates[$column] = null;
                }
            }

            foreach ($files as $key => $file) {
                $column = self::ASSET_COLUMNS[$key] ?? null;
                if (! $column || ! $file instanceof UploadedFile || ! $file->isValid()) {
                    continue;
                }
                if ($branding->{$column}) {
                    Storage::disk('public')->delete($branding->{$column});
                }
                $updates[$column] = $file->store("tenants/{$branding->tenant_id}/branding", 'public');
            }

            if ($updates !== []) {
                $branding->update($updates);
            }

            $this->audit->log(
                event: AuditEventType::TENANT_BRANDING_UPDATED->value,
                action: 'update',
                subject: $branding,
                description: "Branding updated for tenant {$branding->tenant_id} (platform console)"
            );

            return $branding->refresh();
        });
    }

    /** Drop every brand override (assets + scalars); CSS and DKIM are separate concerns. */
    public function resetBranding(TenantBranding $branding): TenantBranding
    {
        return DB::transaction(function () use ($branding): TenantBranding {
            foreach (self::ASSET_COLUMNS as $column) {
                if ($branding->{$column}) {
                    Storage::disk('public')->delete($branding->{$column});
                }
            }

            $branding->update([
                'name' => null,
                'logo_path' => null,
                'logo_dark_path' => null,
                'logo_icon_path' => null,
                'favicon_path' => null,
                'login_background_path' => null,
                'primary_color' => null,
                'secondary_color' => null,
                'email_from_name' => null,
                'email_from_address' => null,
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_BRANDING_UPDATED->value,
                action: 'reset',
                subject: $branding,
                description: "Branding reset to platform defaults for tenant {$branding->tenant_id}"
            );

            return $branding->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // Legacy simple update (kept for API compatibility)
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Custom CSS
    // -------------------------------------------------------------------------

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

    /** Kill switch — disable a tenant's custom CSS without deleting it. */
    public function setCssEnabled(TenantBranding $branding, bool $enabled): TenantBranding
    {
        return DB::transaction(function () use ($branding, $enabled): TenantBranding {
            $branding->update(['css_disabled' => ! $enabled]);

            $this->audit->log(
                event: AuditEventType::TENANT_CSS_UPDATED->value,
                action: $enabled ? 'enable' : 'disable',
                subject: $branding,
                description: 'Custom CSS '.($enabled ? 'enabled' : 'disabled')." for tenant {$branding->tenant_id}"
            );

            return $branding->refresh();
        });
    }

    public function removeCustomCss(TenantBranding $branding): TenantBranding
    {
        return DB::transaction(function () use ($branding): TenantBranding {
            if ($branding->custom_css_path) {
                Storage::disk('public')->delete($branding->custom_css_path);
            }
            $branding->update(['custom_css_path' => null, 'css_disabled' => false]);

            $this->audit->log(
                event: AuditEventType::TENANT_CSS_UPDATED->value,
                action: 'remove',
                subject: $branding,
                description: "Custom CSS removed for tenant {$branding->tenant_id}"
            );

            return $branding->refresh();
        });
    }

    public function getCssContent(TenantBranding $branding): string
    {
        if (! $branding->custom_css_path) {
            return '';
        }

        try {
            return Storage::disk('public')->get($branding->custom_css_path) ?? '';
        } catch (\Throwable) {
            return '';
        }
    }

    // -------------------------------------------------------------------------
    // DKIM
    // -------------------------------------------------------------------------

    public function configureDkim(TenantBranding $branding, string $selector, string $privateKey): TenantBranding
    {
        return DB::transaction(function () use ($branding, $selector, $privateKey): TenantBranding {
            $branding->update([
                'dkim_selector' => $selector,
                'dkim_private_key' => $privateKey,
                'dkim_verified_at' => null, // re-verify after any key change
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

        $records = @dns_get_record($dnsName, DNS_TXT);
        $verified = ! empty($records);

        if ($verified) {
            $branding->update(['dkim_verified_at' => now()]);

            $this->audit->log(
                event: AuditEventType::DKIM_VERIFIED->value,
                action: 'verify',
                subject: $branding,
                description: "DKIM verified for tenant {$branding->tenant_id}"
            );
        }

        return $verified;
    }

    public function clearDkim(TenantBranding $branding): TenantBranding
    {
        return DB::transaction(function () use ($branding): TenantBranding {
            $branding->update([
                'dkim_selector' => null,
                'dkim_private_key' => null,
                'dkim_verified_at' => null,
            ]);

            $this->audit->log(
                event: AuditEventType::DKIM_CONFIGURED->value,
                action: 'remove',
                subject: $branding,
                description: "DKIM configuration removed for tenant {$branding->tenant_id}"
            );

            return $branding->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // Console overview
    // -------------------------------------------------------------------------

    /** Everything the /white-label command center renders, in one payload. */
    public function overview(): array
    {
        $tenants = Tenant::query()->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name ?? $t->id,
                'subdomain' => $t->subdomain,
                'status' => $t->status,
            ])
            ->values();
        $tenantNames = $tenants->keyBy('id');

        $disk = Storage::disk('public');

        $brandings = TenantBranding::query()->orderByDesc('updated_at')->get()->map(function (TenantBranding $b) use ($tenantNames, $disk) {
            $overrides = $this->overridesLayer($b);

            $cssSize = 0;
            if ($b->custom_css_path) {
                try {
                    $cssSize = (int) $disk->size($b->custom_css_path);
                } catch (\Throwable) {
                    $cssSize = 0;
                }
            }

            return [
                'id' => $b->id,
                'tenant_id' => $b->tenant_id,
                'tenant' => $tenantNames[$b->tenant_id]['name'] ?? $b->tenant_id,
                'name' => $b->name,
                'logo_light' => $overrides['logo_light'],
                'logo_dark' => $overrides['logo_dark'],
                'logo_icon' => $overrides['logo_icon'],
                'favicon' => $overrides['favicon'],
                'login_background' => $overrides['login_background'],
                'primary_color' => $b->primary_color,
                'accent_color' => $b->secondary_color,
                'customized' => BrandingPayload::isCustomized($overrides),
                'assets_count' => collect(self::ASSET_COLUMNS)->filter(fn ($col) => (bool) $b->{$col})->count(),
                'has_css' => (bool) $b->custom_css_path,
                'css_disabled' => (bool) $b->css_disabled,
                'css_size' => $cssSize,
                'email_from_name' => $b->email_from_name,
                'email_from_address' => $b->email_from_address,
                'dkim_selector' => $b->dkim_selector,
                'dkim_configured' => (bool) $b->dkim_private_key,
                'dkim_verified_at' => $b->dkim_verified_at?->toIso8601String(),
                'updated_at' => $b->updated_at?->toIso8601String(),
            ];
        })->values();

        $domains = TenantCustomDomain::query()->orderByDesc('created_at')->get()->map(fn (TenantCustomDomain $d) => [
            'id' => $d->id,
            'tenant_id' => $d->tenant_id,
            'tenant' => $tenantNames[$d->tenant_id]['name'] ?? $d->tenant_id,
            'domain' => $d->domain,
            'status' => $d->status,
            'ssl_status' => $d->ssl_status,
            'verified_at' => $d->verified_at?->toIso8601String(),
            'ssl_expires_at' => $d->ssl_expires_at?->toIso8601String(),
            'ssl_days_left' => $d->ssl_expires_at ? (int) now()->diffInDays($d->ssl_expires_at, false) : null,
            'dns_txt_record' => $d->dns_txt_record,
            'created_at' => $d->created_at?->toIso8601String(),
        ])->values();

        $stats = [
            'tenants_total' => $tenants->count(),
            'branded' => $brandings->where('customized', true)->count(),
            'domains_total' => $domains->count(),
            'domains_verified' => $domains->where('status', 'verified')->count(),
            'domains_pending' => $domains->where('status', 'pending')->count(),
            'ssl_active' => $domains->where('ssl_status', 'active')->count(),
            'ssl_expiring_30d' => $domains->filter(fn ($d) => $d['ssl_days_left'] !== null && $d['ssl_days_left'] >= 0 && $d['ssl_days_left'] <= 30)->count(),
            'css_active' => $brandings->filter(fn ($b) => $b['has_css'] && ! $b['css_disabled'])->count(),
            'dkim_configured' => $brandings->where('dkim_configured', true)->count(),
            'dkim_verified' => $brandings->filter(fn ($b) => $b['dkim_verified_at'] !== null)->count(),
        ];

        return [
            'stats' => $stats,
            'brandings' => $brandings,
            'domains' => $domains,
            'tenants' => $tenants,
        ];
    }

    // -------------------------------------------------------------------------
    // Layers
    // -------------------------------------------------------------------------

    /** This row's overrides mapped onto canonical BrandingPayload keys (URLs resolved). */
    public function overridesLayer(TenantBranding $branding): array
    {
        $disk = Storage::disk('public');
        $url = fn (?string $path) => $path ? $disk->url($path) : null;

        return [
            'name' => $branding->name,
            'tagline' => null,
            'logo_light' => $url($branding->logo_path),
            'logo_dark' => $url($branding->logo_dark_path),
            'logo_icon' => $url($branding->logo_icon_path),
            'favicon' => $url($branding->favicon_path),
            'login_background' => $url($branding->login_background_path),
            'primary_color' => $branding->primary_color,
            'accent_color' => $branding->secondary_color,
            'sidebar_theme' => null,
            'email_from_name' => $branding->email_from_name,
            'email_from_address' => $branding->email_from_address,
        ];
    }

    /** The platform's own brand layer (what an unbranded tenant inherits). */
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
