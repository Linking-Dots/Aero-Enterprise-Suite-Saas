<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OAuthProviderController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        $apps = DB::table('sso_configurations')
            ->where('type', 'like', 'oauth_app_%')
            ->get()
            ->map(fn($row) => array_merge(
                json_decode($row->config, true) ?? [],
                ['id' => $row->id, 'type' => $row->type, 'is_enabled' => $row->is_enabled]
            ));

        return Inertia::render('Core/Identity/OAuthProvider', ['apps' => $apps]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'redirect_uris' => ['required', 'array', 'min:1'],
            'scopes'        => ['nullable', 'array'],
            'is_enabled'    => ['boolean'],
        ]);

        $clientId     = 'aeros_client_' . Str::random(16);
        $clientSecret = 'aeros_secret_' . Str::random(32);

        $id = DB::table('sso_configurations')->insertGetId([
            'type'       => 'oauth_app_' . Str::slug($data['name']),
            'is_enabled' => $data['is_enabled'] ?? true,
            'config'     => json_encode(array_merge($data, [
                'client_id'     => $clientId,
                'client_secret' => hash('sha256', $clientSecret),
            ])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->log(AuditEventType::RECORD_CREATED->value, 'oauth_app_created', null, 'OAuth app created', null, null, ['id' => $id]);

        return redirect()->route('core.identity.oauth-provider.index')
            ->with('oauth_credentials', ['client_id' => $clientId, 'client_secret' => $clientSecret])
            ->with('success', 'OAuth app created. Copy the credentials — secret not shown again.');
    }

    public function revoke(int $id, Request $request): RedirectResponse
    {
        DB::table('sso_configurations')->where('id', $id)->update(['is_enabled' => false, 'updated_at' => now()]);
        $this->audit->log(AuditEventType::RECORD_UPDATED->value, 'oauth_app_revoked', null, 'OAuth app revoked', null, null, ['id' => $id]);
        return back()->with('success', 'OAuth app revoked.');
    }
}
