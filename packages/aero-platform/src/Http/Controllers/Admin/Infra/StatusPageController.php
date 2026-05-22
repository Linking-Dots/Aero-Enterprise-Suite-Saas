<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Infra;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\Infra\StoreStatusComponentRequest;
use Aero\Platform\Http\Requests\Admin\Infra\StoreStatusIncidentRequest;
use Aero\Platform\Models\Infra\StatusIncident;
use Aero\Platform\Models\Infra\StatusPageComponent;
use Aero\Platform\Models\Infra\StatusPagePostmortem;
use Aero\Platform\Services\Infra\StatusPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatusPageController extends Controller
{
    public function __construct(private readonly StatusPageService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/StatusPage/Index', [
            'components' => $this->svc->getComponents(),
            'activeIncidents' => StatusIncident::where('status', '!=', 'resolved')
                ->orderByDesc('started_at')
                ->get(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Components
    // -------------------------------------------------------------------------

    public function storeComponent(StoreStatusComponentRequest $request): RedirectResponse
    {
        $this->svc->createComponent($request->validated());

        return back()->with('success', 'Component created.');
    }

    public function updateComponent(StoreStatusComponentRequest $request, StatusPageComponent $component): RedirectResponse
    {
        $this->svc->updateComponent($component, $request->validated());

        return back()->with('success', 'Component updated.');
    }

    public function destroyComponent(StatusPageComponent $component): RedirectResponse
    {
        $this->svc->deleteComponent($component);

        return back()->with('success', 'Component deleted.');
    }

    public function setComponentStatus(Request $request, StatusPageComponent $component): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:operational,degraded,partial_outage,major_outage'],
        ]);

        $this->svc->setComponentStatus($component, $data['status']);

        return back()->with('success', 'Component status updated.');
    }

    // -------------------------------------------------------------------------
    // Incidents
    // -------------------------------------------------------------------------

    public function incidentsIndex(): Response
    {
        return Inertia::render('Platform/Admin/StatusPage/Incidents', [
            'incidents' => StatusIncident::with('postmortem')
                ->orderByDesc('started_at')
                ->paginate(20),
        ]);
    }

    public function storeIncident(StoreStatusIncidentRequest $request): RedirectResponse
    {
        $this->svc->createIncident($request->validated());

        return back()->with('success', 'Incident created.');
    }

    public function updateIncident(StoreStatusIncidentRequest $request, StatusIncident $incident): RedirectResponse
    {
        $this->svc->updateIncident($incident, $request->validated());

        return back()->with('success', 'Incident updated.');
    }

    public function resolveIncident(StatusIncident $incident): RedirectResponse
    {
        $this->svc->resolveIncident($incident);

        return back()->with('success', 'Incident resolved.');
    }

    // -------------------------------------------------------------------------
    // Postmortems
    // -------------------------------------------------------------------------

    public function storePostmortem(Request $request, StatusIncident $incident): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content_md' => ['required', 'string', 'max:100000'],
            'created_by' => ['required', 'integer'],
        ]);

        $this->svc->createPostmortem($incident, $data);

        return back()->with('success', 'Postmortem created.');
    }

    public function publishPostmortem(StatusPagePostmortem $postmortem): RedirectResponse
    {
        $this->svc->publishPostmortem($postmortem);

        return back()->with('success', 'Postmortem published.');
    }

    // -------------------------------------------------------------------------
    // SLA / Uptime
    // -------------------------------------------------------------------------

    public function sla(): Response
    {
        return Inertia::render('Platform/Admin/StatusPage/Sla');
    }

    public function uptime(): Response
    {
        return Inertia::render('Platform/Admin/StatusPage/Uptime');
    }
}
