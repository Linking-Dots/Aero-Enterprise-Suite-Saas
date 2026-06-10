<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Kernel\Audit\AuditEventType;
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class SamlConfigController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    private function getConfig(): array
    {
        $row = DB::table('sso_configurations')->where('type', 'saml')->first();
        if (! $row) {
            return ['is_enabled' => false];
        }

        $config = $row->config ? json_decode($row->config, true) : [];

        return array_merge($config, ['is_enabled' => (bool) $row->is_enabled]);
    }

    public function index(Request $request): Response
    {
        $this->audit->logAccess('saml_config', null, 'SAML configuration', ['config']);

        return Inertia::render('Core/Identity/Saml', [
            'config' => $this->getConfig(),
            'metadata_url' => route('auth.saml.metadata'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled' => ['boolean'],
            'entity_id' => ['nullable', 'string', 'max:255'],
            'sso_url' => ['nullable', 'url'],
            'slo_url' => ['nullable', 'url'],
            'certificate' => ['nullable', 'string'],
            'name_id_format' => ['nullable', 'string'],
            'attribute_mapping' => ['nullable', 'array'],
            'sign_requests' => ['boolean'],
            'auto_provision' => ['boolean'],
        ]);

        $isEnabled = (bool) ($data['is_enabled'] ?? false);
        unset($data['is_enabled']);

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'saml'],
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
            'SAML configuration updated',
            null,
            null,
            ['section' => 'saml_config']
        );

        return back()->with('success', 'SAML configuration saved.');
    }

    public function test(Request $request): RedirectResponse
    {
        $config = $this->getConfig();

        if (empty($config['sso_url'])) {
            return back()->with('error', 'SSO URL is not configured.');
        }

        try {
            $response = Http::timeout(5)->get($config['sso_url']);
            DB::table('sso_configurations')->where('type', 'saml')->update([
                'last_tested_at' => now(),
                'last_test_passed' => $response->successful(),
                'updated_at' => now(),
            ]);

            return back()->with(
                $response->successful() ? 'success' : 'error',
                $response->successful()
                    ? 'SAML IdP is reachable.'
                    : "IdP returned HTTP {$response->status()}."
            );
        } catch (\Throwable $e) {
            return back()->with('error', "Connection failed: {$e->getMessage()}");
        }
    }
}
