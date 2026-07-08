<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\EntitlementOverrideService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Entitlement overrides admin — grant/revoke a module to a tenant outside a
 * purchase, and view the entitlement ledger. The payoff of the tenant_entitlements
 * ledger: comp / trial / grandfather access with a full audit trail.
 */
class EntitlementOverrideController extends Controller
{
    public function __construct(private EntitlementOverrideService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Entitlements/Index', $this->svc->overview());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id'   => ['required', 'string', Rule::exists('tenants', 'id')],
            'module_code' => ['required', 'string', Rule::exists('modules', 'code')],
            'reason'      => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $this->svc->grant($data['tenant_id'], $data['module_code'], $data['reason'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Override granted.');
    }

    public function destroy(int $entitlement): RedirectResponse
    {
        $this->svc->revoke($entitlement);

        return back()->with('success', 'Override revoked.');
    }
}
