<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantProvisioningLog;
use Aero\Platform\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(private TenantProvisioningService $svc) {}

    public function dashboard(): Response
    {
        return Inertia::render('Platform/Admin/Onboarding/Dashboard', [
            'stats' => [
                'pending_approvals' => Tenant::where('status', 'pending')->count(),
                'provisioning' => Tenant::where('status', 'provisioning')->count(),
                'trials' => Tenant::whereNotNull('stripe_trial_ends_at')
                    ->where('stripe_trial_ends_at', '>', now())->count(),
                'today_signups' => Tenant::whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function pending(): Response
    {
        $tenants = Tenant::where('status', 'pending')
            ->with(['domains'])
            ->paginate(25);

        $tenants->getCollection()->transform(function (Tenant $tenant): Tenant {
            $ps = ProductSubscription::with('product')
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['trialing', 'pending', 'active'])
                ->first();
            $tenant->pending_product = $ps?->product;

            return $tenant;
        });

        return Inertia::render('Platform/Admin/Onboarding/Pending', [
            'tenants' => $tenants,
        ]);
    }

    public function approve(Tenant $tenant): RedirectResponse
    {
        $this->svc->approve($tenant);

        return back()->with('success', 'Tenant approved');
    }

    public function reject(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->svc->reject($tenant, $request->string('reason'));

        return back()->with('success', 'Tenant rejected');
    }

    public function provisioning(): Response
    {
        return Inertia::render('Platform/Admin/Onboarding/Provisioning', [
            'logs' => TenantProvisioningLog::with('tenant')
                ->whereIn('status', ['pending', 'running', 'failed'])
                ->orderByDesc('updated_at')
                ->paginate(50),
        ]);
    }

    public function retryProvisioning(Tenant $tenant): RedirectResponse
    {
        $this->svc->retry($tenant);

        return back()->with('success', 'Retry queued');
    }

    public function trials(): Response
    {
        return Inertia::render('Platform/Admin/Onboarding/Trials', [
            'tenants' => Tenant::whereNotNull('stripe_trial_ends_at')
                ->with('domains')
                ->orderBy('stripe_trial_ends_at')
                ->paginate(25),
        ]);
    }

    public function extendTrial(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate(['days' => 'required|integer|min:1|max:90']);
        $this->svc->extendTrial($tenant, $request->integer('days'));

        return back()->with('success', 'Trial extended');
    }

    public function convertTrial(Tenant $tenant): RedirectResponse
    {
        $this->svc->convertTrial($tenant);

        return back()->with('success', 'Trial converted');
    }
}
