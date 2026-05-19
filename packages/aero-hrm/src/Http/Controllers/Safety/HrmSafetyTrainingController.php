<?php

namespace Aero\HRM\Http\Controllers\Safety;

use Aero\HRM\Http\Requests\Safety\AssignTrainingRequest;
use Aero\HRM\Models\HrmSafetyTraining;
use Aero\HRM\Models\HrmSafetyTrainingAssignment;
use Aero\HRM\Services\Safety\SafetyTrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmSafetyTrainingController extends Controller
{
    public function __construct(private SafetyTrainingService $svc) {}

    public function index(): Response
    {
        $trainings = HrmSafetyTraining::withCount('assignments')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Safety/Training/Index', [
            'trainings' => $trainings,
        ]);
    }

    public function store(AssignTrainingRequest $r): RedirectResponse
    {
        $this->svc->assign([
            ...$r->validated(),
            'assigned_by' => $r->user()->id,
        ]);

        return back()->with('success', 'Training assigned.');
    }

    public function complete(HrmSafetyTrainingAssignment $assignment): RedirectResponse
    {
        $this->svc->complete($assignment);

        return back()->with('success', 'Training marked as completed.');
    }
}
