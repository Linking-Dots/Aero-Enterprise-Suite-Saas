<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Notifications;

use Aero\Notifications\Contracts\MailContextResolver;
use Illuminate\Support\Facades\Log;

class PlatformMailContextResolver implements MailContextResolver
{
    public function resolve(): array
    {
        try {
            $settings = \Aero\Platform\Models\PlatformSetting::get('mail_config');
            if ($settings && ! empty($settings['host'])) {
                return [
                    'configured' => true,
                    'driver' => $settings['driver'] ?? 'smtp',
                    'host' => $settings['host'],
                    'port' => (int) ($settings['port'] ?? 587),
                    'username' => $settings['username'] ?? '',
                    'password' => $settings['password'] ?? '',
                    'encryption' => $settings['encryption'] ?? 'tls',
                    'verify_peer' => $settings['verify_peer'] ?? false,
                    'from_address' => $settings['from_address'] ?? config('mail.from.address'),
                    'from_name' => $settings['from_name'] ?? config('mail.from.name', config('app.name')),
                ];
            }
        } catch (\Throwable $e) {
            Log::debug('PlatformMailContextResolver: No platform settings, falling back to env');
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
