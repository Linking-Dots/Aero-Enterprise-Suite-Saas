<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OidcConfigController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function getConfig(): array
    {
        $row = DB::table('sso_configurations')->where('type', 'oidc')->first();
        if (! $row) {
            return ['is_enabled' => false];
        }

        $config = $row->config ? json_decode($row->config, true) : [];

        return array_merge($config, ['is_enabled' => (bool) $row->is_enabled]);
    }

    public function index(): Response
    {
        $this->audit->logAccess('oidc_config', null, 'OIDC configuration', ['client_secret']);

        return Inertia::render('Core/Identity/Oidc', [
            'config' => $this->getConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled' => ['boolean'],
            'issuer_url' => ['nullable', 'url'],
            'client_id' => ['nullable', 'string'],
            'client_secret' => ['nullable', 'string'],
            'scopes' => ['nullable', 'string'],
            'auto_provision' => ['boolean'],
        ]);

        $isEnabled = (bool) ($data['is_enabled'] ?? false);
        unset($data['is_enabled']);

        // Preserve existing secret when blank
        if (empty($data['client_secret'])) {
            $existing = $this->getConfig();
            $data['client_secret'] = $existing['client_secret'] ?? null;
        }

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'oidc'],
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
            'OIDC configuration updated',
            null,
            null,
            ['section' => 'oidc_config']
        );

        return back()->with('success', 'OIDC configuration saved.');
    }
}
