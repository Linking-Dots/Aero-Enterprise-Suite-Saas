<?php

declare(strict_types=1);

namespace Aero\Core\Services;

use Aero\Core\Models\EmailTemplate;
use Aero\Core\Models\SystemSetting;

/**
 * Health-band summary for the unified Settings command center.
 *
 * Every settings GET route renders Core/Settings/Index and includes this small
 * aggregate so the top-of-page health band (security posture, email
 * deliverability, localization, developer access) is populated regardless of
 * which section is active. Cheap reads only — no heavy queries.
 */
class SettingsSummary
{
    public static function build(): array
    {
        $s = app(SystemSettingService::class);

        $loc = SystemSetting::current()->getLocalizationPayload();
        $mailHost = (string) $s->get('mail_host', '');

        $integrationKeys = [
            'integration_slack_enabled',
            'integration_gws_enabled',
            'integration_m365_enabled',
            'integration_zapier_enabled',
        ];
        $integrationsOn = 0;
        foreach ($integrationKeys as $k) {
            if ($s->get($k, false)) {
                $integrationsOn++;
            }
        }

        return [
            'security' => [
                'require_2fa' => (bool) $s->get('require_2fa_admins', false),
                'lockout' => (int) $s->get('max_failed_attempts', 5),
                'session_lifetime' => (int) $s->get('session_lifetime', 120),
            ],
            'email' => [
                'configured' => $mailHost !== '',
                'from' => (string) $s->get('mail_from_email', ''),
            ],
            'localization' => [
                'locale' => $loc['locale'] ?? 'en',
                'timezone' => $loc['timezone'] ?? 'UTC',
                'currency' => $loc['currency'] ?? '',
            ],
            'developer' => [
                'integrations' => $integrationsOn,
                'templates' => EmailTemplate::count(),
            ],
        ];
    }
}
