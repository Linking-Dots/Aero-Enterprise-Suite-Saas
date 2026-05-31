<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RateLimitController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Api/RateLimits', [
            'limits' => [
                'api_global_per_minute' => (int) $this->settings->get('rate_limit_api_global', 60),
                'api_per_key_per_minute' => (int) $this->settings->get('rate_limit_api_per_key', 30),
                'api_burst_allowance' => (int) $this->settings->get('rate_limit_burst', 10),
                'webhook_per_hour' => (int) $this->settings->get('rate_limit_webhook', 100),
                'auth_attempts_per_minute' => (int) $this->settings->get('rate_limit_auth', 5),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'api_global_per_minute' => ['required', 'integer', 'min:1', 'max:10000'],
            'api_per_key_per_minute' => ['required', 'integer', 'min:1', 'max:1000'],
            'api_burst_allowance' => ['required', 'integer', 'min:0', 'max:100'],
            'webhook_per_hour' => ['required', 'integer', 'min:1', 'max:10000'],
            'auth_attempts_per_minute' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $map = [
            'api_global_per_minute' => 'rate_limit_api_global',
            'api_per_key_per_minute' => 'rate_limit_api_per_key',
            'api_burst_allowance' => 'rate_limit_burst',
            'webhook_per_hour' => 'rate_limit_webhook',
            'auth_attempts_per_minute' => 'rate_limit_auth',
        ];

        foreach ($map as $field => $key) {
            $this->settings->set($key, $data[$field]);
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'rate_limits_updated', null, 'API rate limits updated');

        return back()->with('success', 'Rate limits saved.');
    }
}
