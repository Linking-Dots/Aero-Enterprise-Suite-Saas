<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Infra;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\Infra\StoreSecurityIncidentRequest;
use Aero\Platform\Http\Requests\Admin\Infra\UploadPentestReportRequest;
use Aero\Platform\Models\Infra\PentestReport;
use Aero\Platform\Models\Infra\SecurityIncident;
use Aero\Platform\Services\Infra\SecurityCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityCenterController extends Controller
{
    public function __construct(private readonly SecurityCenterService $svc) {}

    public function dashboard(): Response
    {
        return Inertia::render('Platform/Admin/SecurityCenter/Dashboard', [
            'summary' => $this->svc->getDashboardSummary(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Security Incidents
    // -------------------------------------------------------------------------

    public function incidentsIndex(): Response
    {
        return Inertia::render('Platform/Admin/SecurityCenter/Incidents', [
            'incidents' => SecurityIncident::orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function storeIncident(StoreSecurityIncidentRequest $request): RedirectResponse
    {
        $this->svc->createIncident($request->validated());

        return back()->with('success', 'Security incident created.');
    }

    public function notifyTenants(SecurityIncident $incident): RedirectResponse
    {
        $this->svc->notifyTenants($incident);

        return back()->with('success', 'Tenant notifications queued.');
    }

    // -------------------------------------------------------------------------
    // Pentest Reports
    // -------------------------------------------------------------------------

    public function pentestsIndex(): Response
    {
        return Inertia::render('Platform/Admin/SecurityCenter/Pentests', [
            'reports' => PentestReport::orderByDesc('conducted_at')->paginate(25),
        ]);
    }

    public function uploadPentest(UploadPentestReportRequest $request): RedirectResponse
    {
        $this->svc->uploadPentest(
            $request->safe()->except('file'),
            $request->file('file'),
        );

        return back()->with('success', 'Pentest report uploaded.');
    }

    public function shareReport(Request $request, PentestReport $report): RedirectResponse
    {
        $data = $request->validate([
            'tenant_ids' => ['required', 'array'],
            'tenant_ids.*' => ['string'],
        ]);

        $this->svc->shareReport($report, $data['tenant_ids']);

        return back()->with('success', 'Report shared with selected tenants.');
    }
}
