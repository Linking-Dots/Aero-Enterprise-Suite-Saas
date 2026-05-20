<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Domain;
use Aero\Platform\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantDomainController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'domains' => $tenant->domains()->orderByDesc('is_primary')->get(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'domain' => 'required|string|max:255|unique:domains,domain',
            'is_primary' => 'boolean',
        ]);

        DB::transaction(function () use ($tenant, $data) {
            if ($data['is_primary'] ?? false) {
                $tenant->domains()->update(['is_primary' => false]);
            }

            Domain::create([
                'tenant_id' => $tenant->id,
                'domain' => $data['domain'],
                'is_primary' => $data['is_primary'] ?? false,
                'status' => 'pending',
                'ssl_status' => 'pending',
            ]);
        });

        return back()->with('success', 'Domain added');
    }

    public function destroy(Tenant $tenant, Domain $domain): RedirectResponse
    {
        abort_unless($domain->tenant_id === $tenant->id, 404);
        $domain->delete();

        return back()->with('success', 'Domain removed');
    }

    public function verify(Tenant $tenant, Domain $domain): RedirectResponse
    {
        abort_unless($domain->tenant_id === $tenant->id, 404);
        $domain->markAsVerified();

        return back()->with('success', 'Domain verified');
    }
}
