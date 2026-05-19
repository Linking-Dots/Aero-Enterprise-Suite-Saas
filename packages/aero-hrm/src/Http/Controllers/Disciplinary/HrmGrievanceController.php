<?php

namespace Aero\HRM\Http\Controllers\Disciplinary;

use Aero\HRM\Http\Requests\Disciplinary\InvestigateGrievanceRequest;
use Aero\HRM\Http\Requests\Disciplinary\ResolveGrievanceRequest;
use Aero\HRM\Http\Requests\Disciplinary\StoreGrievanceRequest;
use Aero\HRM\Models\HrmGrievance;
use Aero\HRM\Services\Disciplinary\GrievanceService;
use Illuminate\Http\RedirectResponse;
use Aero\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmGrievanceController extends Controller
{
    public function __construct(private GrievanceService $svc) {}

    public function index(): Response
    {
        $grievances = HrmGrievance::with(['filedBy', 'investigator'])
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Disciplinary/Grievances/Index', [
            'grievances' => $grievances,
            'filters' => request()->only(['status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Disciplinary/Grievances/Create', [
            'categories' => ['harassment', 'discrimination', 'workplace_safety', 'policy_violation', 'interpersonal', 'other'],
        ]);
    }

    public function store(StoreGrievanceRequest $r): RedirectResponse
    {
        $employeeId = $r->user()->employee?->id;
        abort_unless($employeeId, 403, 'Only employees can file grievances.');

        $this->svc->file([
            ...$r->validated(),
            'filed_by' => $employeeId,
        ]);

        return redirect()->route('hrm.grievances.index')->with('success', 'Grievance filed.');
    }

    public function show(Request $r, HrmGrievance $grievance): Response
    {
        $user = $r->user();
        $isAdmin = $user->can('hrm.grievances.grievance-list.view');
        // Scope: admin sees all; employee sees only their own
        if (! $isAdmin && $user->employee?->id !== $grievance->filed_by) {
            abort(403);
        }

        return Inertia::render('HRM/Disciplinary/Grievances/Show', [
            'grievance'     => $grievance->load('filedBy', 'againstEmployee', 'investigator:id,name'),
            'timeline'      => $grievance->events()->with('actor:id,name')->orderBy('occurred_at')->get(),
            'investigators' => User::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function investigate(InvestigateGrievanceRequest $r, HrmGrievance $grievance): RedirectResponse
    {
        $this->svc->assignInvestigator($grievance, $r->validated('investigator_id'), $r->user()->id);

        return back()->with('success', 'Investigator assigned.');
    }

    public function resolve(ResolveGrievanceRequest $r, HrmGrievance $grievance): RedirectResponse
    {
        if ($r->boolean('dismiss')) {
            $this->svc->dismiss($grievance, $r->validated('notes'), $r->user()->id);
        } else {
            $this->svc->resolve($grievance, $r->validated('notes'), $r->user()->id);
        }

        return back()->with('success', 'Grievance updated.');
    }
}
