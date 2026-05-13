<?php

declare(strict_types=1);

namespace Aero\Core\Services\Notifications;

use Aero\Contracts\MailContextResolverInterface;
use Aero\Core\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class CoreMailContextResolver implements MailContextResolverInterface
{
    public function resolve(): array
    {
        try {
            $settings = SystemSetting::current()->email_settings ?? [];
            if ($settings && ! empty($settings['driver'])) {
                $driver = $settings['driver'];
                $base = [
                    'configured' => true,
                    'driver' => $driver,
                    'from_address' => $settings['from_address'] ?? config('mail.from.address'),
                    'from_name' => $settings['from_name'] ?? config('mail.from.name', config('app.name')),
                ];

                return match ($driver) {
                    'smtp' => array_merge($base, [
                        'host' => $settings['host'] ?? '127.0.0.1',
                        'port' => (int) ($settings['port'] ?? 587),
                        'username' => $settings['username'] ?? '',
                        'password' => $settings['password'] ?? '',
                        'encryption' => $settings['encryption'] ?? 'tls',
                        'verify_peer' => $settings['verify_peer'] ?? false,
                    ]),
                    'sendmail' => array_merge($base, [
                        'sendmail_path' => $settings['sendmail_path'] ?? '/usr/sbin/sendmail -bs',
                    ]),
                    'mailgun' => array_merge($base, [
                        'mailgun_domain' => $settings['mailgun_domain'] ?? null,
                        'mailgun_secret' => $settings['mailgun_secret'] ?? null,
                        'mailgun_endpoint' => $settings['mailgun_endpoint'] ?? null,
                        'mailgun_scheme' => $settings['mailgun_scheme'] ?? null,
                    ]),
                    'postmark' => array_merge($base, [
                        'postmark_token' => $settings['postmark_token'] ?? null,
                    ]),
                    'ses' => array_merge($base, [
                        'ses_key' => $settings['ses_key'] ?? null,
                        'ses_secret' => $settings['ses_secret'] ?? null,
                        'ses_region' => $settings['ses_region'] ?? null,
                    ]),
                    'log', 'array' => $base,
                    default => array_merge($base, $settings),
                };
            }
        } catch (\Throwable $e) {
            Log::debug('CoreMailContextResolver: No system settings, falling back to env');
        }

        return [
            'configured' => true,
            'driver' => config('mail.default', 'smtp'),
            'host' => config('mail.mailers.smtp.host', '127.0.0.1'),
            'port' => (int) config('mail.mailers.smtp.port', 587),
            'username' => config('mail.mailers.smtp.username', ''),
            'password' => config('mail.mailers.smtp.password', ''),
            'encryption' => config('mail.mailers.smtp.encryption', 'tls'),
            'verify_peer' => false,
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name', config('app.name')),
        ];
    }
}
