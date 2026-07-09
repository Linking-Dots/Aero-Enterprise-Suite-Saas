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
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $svc
    ) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Plans/P2/Index', $this->svc->overview());
    }

    /** Drawer payload: subscribers, revenue roll-up, audit activity. */
    public function detail(Plan $plan): JsonResponse
    {
        return response()->json($this->svc->detail((string) $plan->id));
    }

    public function show(Plan $plan): Response
    {
        $plan->loadCount(['subscriptions as active_count' => fn ($q) => $q->where('status', 'active')]);

        return Inertia::render('Platform/Admin/Plans/Show', [
            'plan' => $plan,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Admin/Plans/Form');
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
        return Inertia::render('Platform/Admin/Plans/Form', [
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

    /** Toggle a plan's visibility on the public pricing page. */
    public function togglePublic(Request $request, Plan $plan): RedirectResponse
    {
        $public = $request->boolean('public', ! $plan->is_public);
        $this->svc->setPublic($plan, $public);

        return back()->with('success', $public ? 'Plan published.' : 'Plan unpublished.');
    }

    /** Toggle a plan's featured (recommended) flag. */
    public function toggleFeatured(Request $request, Plan $plan): RedirectResponse
    {
        $featured = $request->boolean('featured', ! $plan->is_featured);
        $this->svc->setFeatured($plan, $featured);

        return back()->with('success', $featured ? 'Plan marked as featured.' : 'Plan unfeatured.');
    }

    /** Persist a new display order for the public pricing page. */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['string', 'exists:plans,id'],
        ]);

        $this->svc->reorder($data['ids']);

        return back()->with('success', 'Plan order updated.');
    }

    /** Stream every plan as CSV. */
    public function export(): StreamedResponse
    {
        $rows = $this->svc->exportRows();
        $filename = 'plans-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Slug', 'Tier', 'Status', 'Visibility', 'Featured', 'Monthly', 'Annual', 'Currency', 'Subscribers', 'Trials', 'MRR']);
            foreach ($rows as $r) {
                fputcsv($out, array_values($r));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
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
