<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Kernel\Audit\AuditEventType;
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ScimConfigController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(): Response
    {
        $row = DB::table('sso_configurations')->where('type', 'scim')->first();
        $config = $row && $row->config ? json_decode($row->config, true) : [];

        return Inertia::render('Core/Identity/Scim', [
            'is_enabled' => (bool) ($row->is_enabled ?? false),
            'scim_url' => url('/scim/v2'),
            'has_token' => ! empty($row->scim_token_hash ?? null),
            'config' => $config,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(['is_enabled' => ['boolean']]);

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'scim'],
            [
                'is_enabled' => $request->boolean('is_enabled'),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->audit->log(
            AuditEventType::SCIM_ENDPOINT_CONFIGURED->value,
            'configured',
            null,
            'SCIM endpoint configuration updated'
        );

        return back()->with('success', 'SCIM configuration saved.');
    }

    public function rotateToken(Request $request): RedirectResponse
    {
        $rawToken = 'scim_'.Str::random(48);

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'scim'],
            [
                'scim_token_hash' => hash('sha256', $rawToken),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->audit->log(
            AuditEventType::SCIM_TOKEN_ROTATED->value,
            'rotated',
            null,
            'SCIM bearer token rotated'
        );

        return redirect()->route('core.identity.scim.index')
            ->with('scim_token', $rawToken)
            ->with('success', 'SCIM token rotated. Copy it now — it will not be shown again.');
    }
}
