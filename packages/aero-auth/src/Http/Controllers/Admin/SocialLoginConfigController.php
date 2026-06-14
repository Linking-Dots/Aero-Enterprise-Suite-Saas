<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Kernel\Audit\AuditEventType;
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SocialLoginConfigController extends Controller
{
    private const PROVIDERS = ['google', 'microsoft', 'github', 'apple'];

    public function __construct(private AuditServiceInterface $audit) {}

    private function getProviderConfig(string $provider): array
    {
        $row = DB::table('sso_configurations')->where('type', "social_{$provider}")->first();
        $config = $row && $row->config ? json_decode($row->config, true) : [];

        return array_merge([
            'is_enabled' => (bool) ($row->is_enabled ?? false),
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $config['client_secret'] ?? '',
            'redirect_uri' => url("/auth/social/{$provider}/callback"),
        ], $config);
    }

    public function index(Request $request): Response
    {
        $this->audit->logAccess('social_login_config', null, 'Social login config', ['client_secrets']);

        $providers = [];
        foreach (self::PROVIDERS as $p) {
            $providers[$p] = $this->getProviderConfig($p);
        }

        return Inertia::render('Core/Identity/Social', [
            'providers' => $providers,
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        abort_if(! in_array($provider, self::PROVIDERS, true), 404);

        $data = $request->validate([
            'is_enabled' => ['boolean'],
            'client_id' => ['nullable', 'string'],
            'client_secret' => ['nullable', 'string'],
            'scopes' => ['nullable', 'string'],
        ]);

        $isEnabled = (bool) ($data['is_enabled'] ?? false);
        unset($data['is_enabled']);

        // Blank client_secret = keep existing
        $existing = $this->getProviderConfig($provider);
        if (empty($data['client_secret'])) {
            $data['client_secret'] = $existing['client_secret'] ?? null;
        }

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => "social_{$provider}"],
            [
                'is_enabled' => $isEnabled,
                'config' => json_encode($data),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->audit->log(
            AuditEventType::PLATFORM_SETTING_UPDATED->value,
            'updated',
            null,
            "Social login provider '{$provider}' updated",
            null,
            null,
            ['section' => 'social_login', 'provider' => $provider]
        );

        return back()->with('success', ucfirst($provider).' OAuth updated.');
    }
}
