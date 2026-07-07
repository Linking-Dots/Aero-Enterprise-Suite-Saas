<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\PlanStoreRequest;
use Aero\Platform\Http\Requests\Admin\PlanUpdateRequest;
use Aero\Platform\Models\Plan;
use Aero\Platform\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Plans/Index', [
            'plans'   => $this->svc->list($request->only(['status', 'tier', 'search'])),
            'stats'   => $this->svc->stats(),
            'filters' => $request->only(['status', 'tier', 'search']),
        ]);
    }

    public function show(Plan $plan): Response
    {
        $plan->loadCount(['subscriptions as active_count' => fn ($q) => $q->where('status', 'active')]);

        return Inertia::render('Platform/Admin/Plans/P2/Show', [
            'plan' => $plan,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Admin/Plans/P2/Form');
    }

    public function store(PlanStoreRequest $request): RedirectResponse
    {
        $plan = $this->svc->create($request->validated());

        return redirect()
            ->route('platform.admin.plans.show', $plan)
            ->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Platform/Admin/Plans/P2/Form', [
            'plan' => $plan,
        ]);
    }

    public function update(PlanUpdateRequest $request, Plan $plan): RedirectResponse
    {
        $this->svc->update($plan, $request->validated());

        return back()->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $this->svc->delete($plan);

        return redirect()
            ->route('platform.admin.plans.index')
            ->with('success', 'Plan deleted.');
    }

    public function archive(Plan $plan): RedirectResponse
    {
        $this->svc->archive($plan);

        return back()->with('success', 'Plan archived.');
    }

    public function clone(Plan $plan): RedirectResponse
    {
        $copy = $this->svc->clone($plan);

        return redirect()->route('platform.admin.plans.show', $copy)->with('success', 'Plan cloned');
    }

    public function stats(Plan $plan): JsonResponse
    {
        $active = $plan->subscriptions()->where('status', 'active')->count();
        $mrr = $plan->subscriptions()->where('status', 'active')->sum('amount');

        return response()->json([
            'active_subscribers' => $active,
            'mrr' => number_format((float) $mrr, 2),
            'features_count' => is_array($plan->features) ? count($plan->features) : 0,
        ]);
    }
}
