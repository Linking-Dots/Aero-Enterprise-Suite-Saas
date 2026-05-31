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

class ApiKeyController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): Response
    {
        $keys = DB::table('api_keys')
            ->select(['id', 'name', 'key_prefix', 'scopes', 'expires_at', 'last_used_at', 'is_active', 'created_at'])
            ->orderByDesc('created_at')
            ->paginate(50)
            ->through(function ($key) {
                $key->scopes = $key->scopes ? json_decode($key->scopes, true) : [];

                return $key;
            });

        return Inertia::render('Core/Api/Keys', [
            'keys' => $keys,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'scopes'     => ['nullable', 'array'],
            'scopes.*'   => ['string'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $rawKey  = 'aeros_'.Str::random(40);
        $keyHash = hash('sha256', $rawKey);
        $prefix  = substr($rawKey, 0, 8);

        $id = DB::transaction(function () use ($request, $rawKey, $keyHash, $prefix) {
            return DB::table('api_keys')->insertGetId([
                'name'       => $request->input('name'),
                'token_hash' => $keyHash,
                'key_prefix' => $prefix,
                'scopes'     => json_encode($request->input('scopes', [])),
                'expires_at' => $request->input('expires_at'),
                'created_by' => $request->user()->id,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->audit->log(
            AuditEventType::API_KEY_CREATED->value,
            'created',
            null,
            'API key created',
            null,
            null,
            ['key_id' => $id, 'name' => $request->input('name')]
        );

        return redirect()->route('core.api.keys.index')
            ->with('api_key', $rawKey)
            ->with('success', 'API key created. Copy it now — it will not be shown again.');
    }

    public function revoke(int $id, Request $request): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            DB::table('api_keys')->where('id', $id)->update([
                'is_active'  => false,
                'updated_at' => now(),
            ]);
        });

        $this->audit->log(
            AuditEventType::API_KEY_REVOKED->value,
            'revoked',
            null,
            'API key revoked',
            null,
            null,
            ['key_id' => $id]
        );

        return back()->with('success', 'API key revoked.');
    }
}
