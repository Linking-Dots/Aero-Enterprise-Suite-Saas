<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\QuotaEnforcementSetting;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\QuotaAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuotaController extends Controller
{
    public function __construct(private QuotaAdminService $svc) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Quotas/Index', [
            'overrides' => $this->svc->listOverrides($request->only(['resource', 'tenant_id'])),
            'analytics' => $this->svc->analytics(),
            'filters' => $request->only(['resource', 'tenant_id']),
        ]);
    }

    public function override(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'resource' => ['required', 'string', 'max:64'],
            'limit_value' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $this->svc->setOverride(
            $tenant,
            $data['resource'],
            $data['limit_value'],
            $data['reason'] ?? null,
            $data['expires_at'] ?? null
        );

        return back()->with('success', 'Override saved.');
    }

    public function removeOverride(Tenant $tenant, string $resource): RedirectResponse
    {
        $this->svc->removeOverride($tenant, $resource);

        return back()->with('success', 'Override removed.');
    }

    public function settings(): Response
    {
        return Inertia::render('Platform/Admin/Quotas/Settings', [
            'settings' => QuotaEnforcementSetting::orderBy('resource')->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'resource' => ['required', 'string', 'max:64'],
            'default_limit' => ['required', 'integer', 'min:0'],
            'warning_threshold_pct' => ['nullable', 'integer', 'between:1,100'],
            'hard_limit_pct' => ['nullable', 'integer', 'between:1,200'],
            'action' => ['required', 'in:warn,throttle,block'],
        ]);

        $this->svc->updateSettings($data['resource'], $data);

        return back()->with('success', 'Settings updated.');
    }
}
