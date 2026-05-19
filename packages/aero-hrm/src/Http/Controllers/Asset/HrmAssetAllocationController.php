<?php

namespace Aero\HRM\Http\Controllers\Asset;

use Aero\HRM\Models\HrmAsset;
use Aero\HRM\Models\HrmAssetAllocation;
use Aero\HRM\Services\Asset\AssetAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class HrmAssetAllocationController extends Controller
{
    public function __construct(private AssetAllocationService $svc) {}

    public function store(Request $r, HrmAsset $asset): RedirectResponse
    {
        $r->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'condition' => ['nullable', 'string', 'in:good,fair,poor'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->svc->allocate(
            $asset,
            (int) $r->input('employee_id'),
            $r->only('condition', 'notes'),
            (int) $r->user()->id
        );

        return redirect()->route('hrm.assets.show', $asset)->with('success', 'Asset allocated.');
    }

    public function returnAsset(Request $r, HrmAssetAllocation $allocation): RedirectResponse
    {
        $r->validate([
            'condition' => ['nullable', 'string', 'in:good,fair,poor'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->svc->returnAsset(
            $allocation,
            $r->only('condition', 'notes'),
            (int) $r->user()->id
        );

        return back()->with('success', 'Asset returned.');
    }
}
