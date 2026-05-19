<?php

namespace Aero\HRM\Http\Controllers\Asset;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Requests\Asset\StoreAssetRequest;
use Aero\HRM\Http\Requests\Asset\UpdateAssetRequest;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmAsset;
use Aero\HRM\Models\HrmAssetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmAssetController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(): Response
    {
        $assets = HrmAsset::with(['category', 'currentAllocation.employee'])
            ->when(request('category_id'), fn ($q, $v) => $q->where('category_id', $v))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->when(request('q'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%")->orWhere('tag', 'like', "%{$v}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Assets/Index', [
            'assets' => $assets,
            'categories' => HrmAssetCategory::orderBy('name')->get(['id', 'name']),
            'filters' => request()->only(['category_id', 'status', 'q']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Assets/Create', [
            'categories' => HrmAssetCategory::where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreAssetRequest $r): RedirectResponse
    {
        $data = $r->validated();

        if ($r->hasFile('photo')) {
            $data['photo_path'] = $r->file('photo')->store('assets/photos', 'public');
        }

        unset($data['photo']);

        $asset = DB::transaction(function () use ($data) {
            $asset = HrmAsset::create($data);
            $this->audit->log(event: 'ASSET_CREATED', action: 'create', subject: $asset, description: "Created asset: {$asset->tag} — {$asset->name}");

            return $asset;
        });

        return redirect()->route('hrm.assets.show', $asset)->with('success', 'Asset created.');
    }

    public function show(HrmAsset $asset): Response
    {
        $asset->load(['category', 'allocations.employee', 'currentAllocation.employee']);

        return Inertia::render('HRM/Assets/Show', [
            'asset' => $asset,
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateAssetRequest $r, HrmAsset $asset): RedirectResponse
    {
        $data = $r->validated();

        if ($r->hasFile('photo')) {
            $data['photo_path'] = $r->file('photo')->store('assets/photos', 'public');
        }

        unset($data['photo']);

        DB::transaction(function () use ($asset, $data) {
            $asset->update($data);
            $this->audit->log(event: 'ASSET_UPDATED', action: 'update', subject: $asset, description: "Updated asset: {$asset->tag} — {$asset->name}");
        });

        return back()->with('success', 'Asset updated.');
    }

    public function destroy(HrmAsset $asset): RedirectResponse
    {
        abort_if($asset->allocations()->whereNull('returned_at')->exists(), 422, 'Asset is currently allocated — return it first.');

        DB::transaction(function () use ($asset) {
            $tag = $asset->tag;
            $name = $asset->name;
            $asset->delete();
            $this->audit->log(event: 'ASSET_DELETED', action: 'delete', subject: $asset, description: "Deleted asset: {$tag} — {$name}");
        });

        return back()->with('success', 'Asset deleted.');
    }
}
