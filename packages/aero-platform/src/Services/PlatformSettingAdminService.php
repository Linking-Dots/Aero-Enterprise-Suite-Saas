<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\PlatformSetting;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Platform Setting Admin Service
 *
 * Provides granular update methods per settings section:
 * general, branding, email, localization, maintenance, infrastructure.
 */
class PlatformSettingAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function current(): PlatformSetting
    {
        return PlatformSetting::current();
    }

    public function updateGeneral(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $setting->update([
                'site_name' => $data['site_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'tagline' => $data['tagline'] ?? null,
                'support_email' => $data['support_email'] ?? null,
                'support_phone' => $data['support_phone'] ?? null,
                'marketing_url' => $data['marketing_url'] ?? null,
                'metadata' => array_merge($setting->metadata ?? [], [
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'date_format' => $data['date_format'] ?? 'Y-m-d',
                    'currency' => $data['currency'] ?? 'USD',
                ]),
            ]);

            $this->audit->log(
                event: AuditEventType::PLATFORM_SETTING_UPDATED->value,
                action: 'edit',
                subject: $setting,
                description: 'Platform general settings updated',
            );

            return $setting->refresh();
        });
    }

    public function updateBranding(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $setting->branding = array_merge($setting->branding ?? [], [
                'primary_color' => $data['primary_color'] ?? '#0f172a',
                'accent_color' => $data['accent_color'] ?? '#818cf8',
            ]);
            $setting->save();

            // Media uploads (logo, favicon) are handled via Spatie Media Library in the controller.

            $this->audit->log(
                event: AuditEventType::PLATFORM_SETTING_UPDATED->value,
                action: 'edit',
                subject: $setting,
                description: 'Platform branding updated',
            );

            return $setting->refresh();
        });
    }

    public function updateEmail(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $payload = $setting->email_settings ?? [];
            $payload['host'] = $data['host'];
            $payload['port'] = (int) $data['port'];
            $payload['username'] = $data['username'] ?? null;
            $payload['encryption'] = $data['encryption'] ?? null;
            $payload['from_email'] = $data['from_email'];
            $payload['from_name'] = $data['from_name'] ?? null;

            if (! empty($data['password'])) {
                $payload['password'] = Crypt::encryptString($data['password']);
            }

            $setting->email_settings = $payload;
            $setting->save();

            $this->audit->log(
                event: AuditEventType::PLATFORM_SETTING_UPDATED->value,
                action: 'edit',
                subject: $setting,
                description: 'Platform SMTP settings updated',
            );

            return $setting->refresh();
        });
    }

    public function sendTestEmail(string $to): array
    {
        try {
            Mail::raw(
                'AEOS365 platform email test — if you received this, your SMTP is correctly configured.',
                fn (Message $m) => $m->to($to)->subject('AEOS365 Test Email')
            );

            $this->audit->log(
                event: AuditEventType::PLATFORM_SETTING_UPDATED->value,
                action: 'test',
                subject: $this->current(),
                description: "Test email sent to {$to}",
            );

            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateLocalization(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $setting->metadata = array_merge($setting->metadata ?? [], [
                'default_locale' => $data['default_locale'],
                'available_locales' => $data['available_locales'] ?? ['en'],
                'timezone' => $data['timezone'] ?? 'UTC',
                'date_format' => $data['date_format'] ?? 'Y-m-d',
                'first_day_of_week' => $data['first_day_of_week'] ?? 1,
            ]);
            $setting->save();

            $this->audit->log(
                event: AuditEventType::PLATFORM_SETTING_UPDATED->value,
                action: 'edit',
                subject: $setting,
                description: 'Localization updated',
            );

            return $setting->refresh();
        });
    }

    public function toggleMaintenance(bool $enable, ?string $message): PlatformSetting
    {
        return DB::transaction(function () use ($enable, $message) {
            $setting = $this->current();

            if ($enable) {
                $setting->enableMaintenanceMode($message);
            } else {
                $setting->disableMaintenanceMode();
            }

            $this->audit->log(
                event: AuditEventType::PLATFORM_SETTING_UPDATED->value,
                action: 'toggle',
                subject: $setting,
                description: $enable ? "Maintenance enabled: {$message}" : 'Maintenance disabled',
            );

            return $setting->refresh();
        });
    }

    public function updateInfrastructure(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $hosting = $setting->hosting_settings ?? [];
            $hosting['mode'] = $data['mode'] ?? PlatformSetting::HOSTING_MODE_DEDICATED;
            $hosting['cpanel_host'] = $data['cpanel_host'] ?? null;
            $hosting['cpanel_port'] = (int) ($data['cpanel_port'] ?? 2083);
            $hosting['cpanel_username'] = $data['cpanel_username'] ?? null;

            if (! empty($data['cpanel_api_token'])) {
                $hosting['cpanel_api_token'] = Crypt::encryptString($data['cpanel_api_token']);
            }

            $hosting['cpanel_db_user'] = $data['cpanel_db_user'] ?? null;
            $setting->hosting_settings = $hosting;
            $setting->save();

            $this->audit->log(
                event: AuditEventType::PLATFORM_SETTING_UPDATED->value,
                action: 'edit',
                subject: $setting,
                description: 'Infrastructure settings updated',
            );

            return $setting->refresh();
        });
    }
}
