<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class SystemSetting extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const DEFAULT_SLUG = 'default';

    public const MEDIA_LOGO_LIGHT = 'brand_logo_light';

    public const MEDIA_LOGO_DARK = 'brand_logo_dark';

    public const MEDIA_FAVICON = 'brand_favicon';

    public const MEDIA_LOGIN_BACKGROUND = 'brand_login_background';

    protected $fillable = [
        'slug',
        'company_name',
        'legal_name',
        'tagline',
        'contact_person',
        'support_email',
        'support_phone',
        'website_url',
        'timezone',
        'currency',
        'locale',
        'date_format',
        'time_format',
        'first_day_of_week',
        'default_dark_mode',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'branding',
        'metadata',
        'email_settings',
        'sms_settings',
        'notification_channels',
        'integrations',
        'advanced',
        'organization',
        'tax_id',
        'vat_number',
        'registration_number',
        'industry',
        'mobile_number',
        'fax',
        'fiscal_year_start',
        'fiscal_year_end',
    ];

    protected $casts = [
        'branding' => 'array',
        'metadata' => 'array',
        'email_settings' => 'array',
        'sms_settings' => 'array',
        'notification_channels' => 'array',
        'integrations' => 'array',
        'advanced' => 'array',
        'organization' => 'array',
        'fiscal_year_start' => 'date',
        'fiscal_year_end' => 'date',
    ];

    protected $attributes = [
        'branding' => '[]',
        'metadata' => '[]',
        'email_settings' => '[]',
        'sms_settings' => '[]',
        'notification_channels' => '[]',
        'integrations' => '[]',
        'advanced' => '[]',
        'organization' => '[]',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(
            ['slug' => self::DEFAULT_SLUG],
            ['company_name' => config('app.name', 'aeos365')]
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_LOGO_LIGHT)->singleFile();
        $this->addMediaCollection(self::MEDIA_LOGO_DARK)->singleFile();
        $this->addMediaCollection(self::MEDIA_FAVICON)->singleFile();
        $this->addMediaCollection(self::MEDIA_LOGIN_BACKGROUND)->singleFile();
    }

    public function getBrandingPayload(): array
    {
        $branding = $this->branding ?? [];

        return array_merge([
            'logo_light' => $this->getFirstMediaUrl(self::MEDIA_LOGO_LIGHT) ?: data_get($branding, 'logo_light'),
            'logo_dark' => $this->getFirstMediaUrl(self::MEDIA_LOGO_DARK) ?: data_get($branding, 'logo_dark'),
            'favicon' => $this->getFirstMediaUrl(self::MEDIA_FAVICON) ?: data_get($branding, 'favicon'),
            'login_background' => $this->getFirstMediaUrl(self::MEDIA_LOGIN_BACKGROUND) ?: data_get($branding, 'login_background'),
            'primary_color' => data_get($branding, 'primary_color', '#0f172a'),
            'accent_color' => data_get($branding, 'accent_color', '#6366f1'),
        ], $branding);
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

    public function getLocalizationPayload(): array
    {
        return [
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'locale' => $this->locale,
            'date_format' => $this->date_format,
            'time_format' => $this->time_format,
            'first_day_of_week' => $this->first_day_of_week,
        ];
    }

    public function getOrganizationSummary(): array
    {
        return [
            'company_name' => $this->company_name,
            'legal_name' => $this->legal_name,
            'tagline' => $this->tagline,
            'contact_person' => $this->contact_person,
            'support_email' => $this->support_email,
            'support_phone' => $this->support_phone,
            'website_url' => $this->website_url,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'default_dark_mode' => (bool) $this->default_dark_mode,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'tax_id' => $this->tax_id,
            'vat_number' => $this->vat_number,
            'registration_number' => $this->registration_number,
            'industry' => $this->industry,
            'mobile_number' => $this->mobile_number,
            'fax' => $this->fax,
            'fiscal_year_start' => $this->fiscal_year_start?->toDateString(),
            'fiscal_year_end' => $this->fiscal_year_end?->toDateString(),
        ];
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('web')
            ->width(512)
            ->optimize()
            ->nonQueued();
    }
}
