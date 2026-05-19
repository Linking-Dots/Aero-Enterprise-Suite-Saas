<?php

namespace Aero\HRM\Http\Controllers\Benefits;

use Aero\HRM\Http\Requests\Benefits\StoreBenefitRequest;
use Aero\HRM\Http\Requests\Benefits\UpdateBenefitRequest;
use Aero\HRM\Models\HrmBenefit;
use Aero\HRM\Services\Benefits\BenefitCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class BenefitCatalogController extends Controller
{
    public function __construct(private BenefitCatalogService $catalog) {}

    public function index(Request $r): Response
    {
        $filters = $r->only(['search', 'category', 'active']);

        return Inertia::render('HRM/Benefits/Catalog/Index', [
            'benefits' => HrmBenefit::query()
                ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%$s%"))
                ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
                ->when(isset($filters['active']), fn ($q) => $q->where('active', (bool) $filters['active']))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'categories' => ['health', 'dental', 'vision', 'life', 'disability', 'pension', 'wellness', 'other'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Benefits/Catalog/Create', [
            'categories' => ['health', 'dental', 'vision', 'life', 'disability', 'pension', 'wellness', 'other'],
            'frequencies' => ['monthly', 'biweekly', 'weekly', 'annual'],
        ]);
    }

    public function store(StoreBenefitRequest $r): RedirectResponse
    {
        $this->catalog->create($r->validated());

        return redirect()->route('hrm.benefits.catalog.index')->with('success', 'Benefit created.');
    }

    public function update(UpdateBenefitRequest $r, HrmBenefit $benefit): RedirectResponse
    {
        $this->catalog->update($benefit, $r->validated());

        return back()->with('success', 'Benefit updated.');
    }

    public function destroy(HrmBenefit $benefit): RedirectResponse
    {
        $this->catalog->delete($benefit);

        return back()->with('success', 'Benefit deleted.');
    }
}
