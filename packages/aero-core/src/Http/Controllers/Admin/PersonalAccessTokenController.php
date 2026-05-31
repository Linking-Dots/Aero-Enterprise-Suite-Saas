<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PersonalAccessTokenController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): Response
    {
        $tokens = DB::table('api_keys')
            ->where('created_by', $request->user()->id)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('Core/Api/Pat', ['tokens' => $tokens]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $rawToken = 'pat_'.Str::random(40);
        $id = DB::table('api_keys')->insertGetId([
            'name' => $request->name,
            'token_hash' => hash('sha256', $rawToken),
            'key_prefix' => substr($rawToken, 0, 8),
            'scopes' => json_encode(['personal']),
            'expires_at' => $request->expires_at,
            'created_by' => $request->user()->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->log(AuditEventType::API_KEY_CREATED->value, 'created', null, 'PAT created', null, null, ['id' => $id]);

        return redirect()->route('core.api.pat.index')
            ->with('pat_token', $rawToken)
            ->with('success', 'Personal access token created. Copy it now — it won\'t be shown again.');
    }

    public function revoke(int $id, Request $request): RedirectResponse
    {
        DB::table('api_keys')->where('id', $id)->where('created_by', $request->user()->id)->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
        $this->audit->log(AuditEventType::API_KEY_REVOKED->value, 'revoked', null, 'PAT revoked', null, null, ['id' => $id]);

        return back()->with('success', 'Token revoked.');
    }
}
