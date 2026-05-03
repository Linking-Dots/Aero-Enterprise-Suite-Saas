<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Notifications;

use Aero\Notifications\Contracts\SmsContextResolver;
use Illuminate\Support\Facades\Log;

class PlatformSmsContextResolver implements SmsContextResolver
{
    public function resolve(): array
    {
        try {
            $config = \Aero\Platform\Models\PlatformSetting::current()->sms_settings ?? [];
            if ($config && ! empty($config['provider'])) {
                return ['configured' => true, 'provider' => $config['provider'], 'credentials' => $config['credentials'] ?? []];
            }
        } catch (\Throwable $e) {
            Log::debug('PlatformSmsContextResolver: No SMS config');
        }
        return ['configured' => false, 'provider' => config('services.sms.default', 'log'), 'credentials' => []];
    }
}
