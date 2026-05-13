<?php

declare(strict_types=1);

namespace Aero\Core\Services\Notifications;

use Aero\Contracts\SmsContextResolverInterface;
use Aero\Core\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class CoreSmsContextResolver implements SmsContextResolverInterface
{
    public function resolve(): array
    {
        try {
            $config = SystemSetting::current()->sms_settings ?? [];
            if ($config && ! empty($config['provider'])) {
                return ['configured' => true, 'provider' => $config['provider'], 'credentials' => $config['credentials'] ?? []];
            }
        } catch (\Throwable $e) {
            Log::debug('CoreSmsContextResolver: No SMS config');
        }

        return ['configured' => false, 'provider' => config('services.sms.default', 'log'), 'credentials' => []];
    }
}
