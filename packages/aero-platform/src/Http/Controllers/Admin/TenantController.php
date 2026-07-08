<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\TenantStoreRequest;
use Aero\Platform\Http\Requests\Admin\TenantUpdateRequest;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantAdminService;
use Aero\Platform\Services\TenantImpersonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantController extends Controller
{
    public function __construct(
        private TenantAdminService $svc,
        private TenantImpersonationService $impersonation,
    ) {}

    /**
     * Tenants command centre — the full overview payload (rows + stats + trend +
     * plan mix + sparklines). Mirrors InvoiceController::index → overview().
     */
    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Tenants/P2/Index', $this->svc->overview());
    }

    /** Drawer payload: subscription snapshot, invoices, and audit activity. */
    public function detail(Tenant $tenant): JsonResponse
    {
        return response()->json($this->svc->detail((string) $tenant->id));
    }

    /** Stream every tenant as CSV. */
    public function export(): StreamedResponse
    {
        $rows = $this->svc->exportRows();
        $filename = 'tenants-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Subdomain', 'Email', 'Type', 'Status', 'Plan', 'Currency', 'MRR', 'Outstanding', 'Joined']);
            foreach ($rows as $r) {
                fputcsv($out, array_values($r));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Admin/Tenants/Create', [
            'plans' => Plan::where('status', 'active')->orderBy('name')->get(['id', 'name', 'price_monthly']),
            'products' => Product::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'module_code', 'monthly_price']),
        ]);
    }

    public function store(TenantStoreRequest $request): RedirectResponse
    {
        $tenant = $this->svc->create($request->validated());

        return redirect()->route('platform.admin.tenants.show', $tenant)
            ->with('success', 'Tenant created and queued for provisioning');
    }

    public function show(Tenant $tenant): Response
    {
        return Inertia::render('Platform/Admin/Tenants/Show', [
            'tenant' => $this->svc->show($tenant->id),
        ]);
    }

    public function update(TenantUpdateRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->svc->update($tenant, $request->validated());

        return back()->with('success', 'Tenant updated');
    }

    public function destroy(Tenant $tenant, Request $request): RedirectResponse
    {
        $this->svc->purge($tenant, $request->user()->id);

        return redirect()->route('platform.admin.tenants.index')
            ->with('success', 'Tenant purged');
    }

    public function suspend(Tenant $tenant, Request $request): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $this->svc->suspend($tenant, $request->string('reason'));

        return back()->with('success', 'Tenant suspended');
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        $this->svc->activate($tenant);

        return back()->with('success', 'Tenant activated');
    }

    public function freeze(Tenant $tenant): RedirectResponse
    {
        $this->svc->freeze($tenant);

        return back()->with('success', 'Tenant frozen');
    }

    public function unfreeze(Tenant $tenant): RedirectResponse
    {
        $this->svc->unfreeze($tenant);

        return back()->with('success', 'Tenant unfrozen');
    }

    public function archive(Tenant $tenant): RedirectResponse
    {
        $this->svc->archive($tenant);

        return back()->with('success', 'Tenant archived');
    }

    public function restore(Tenant $tenant): RedirectResponse
    {
        $this->svc->restore($tenant);

        return back()->with('success', 'Tenant restored');
    }

    public function impersonate(Tenant $tenant, Request $request): RedirectResponse
    {
        $token = $this->impersonation->start($tenant, $request->user()->id);

        $domain = $tenant->domains()->where('is_primary', true)->first()
            ?? $tenant->domains()->first();

        if (! $domain) {
            return back()->with('error', 'Tenant has no domain configured');
        }

        $cookie = cookie('_impersonate', $token, 60, '/', null, true, true, false, 'Strict');

        return redirect()->away("https://{$domain->domain}/")->withCookie($cookie);
    }
}
