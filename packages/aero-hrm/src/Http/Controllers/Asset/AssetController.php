<?php

namespace Aero\HRM\Http\Controllers\Asset;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Asset;
use Aero\HRM\Models\AssetAllocation;
use Aero\HRM\Models\AssetCategory;
use Aero\HRM\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index()
    {
        return Inertia::render('HRM/Assets/AssetsIndex', [
            'title' => 'Asset Management',
            'categories' => AssetCategory::active()->get(),
        ]);
    }

    /**
     * Display asset allocations page.
     */
    public function allocations()
    {
        return Inertia::render('HRM/Assets/AssetAllocationsIndex', [
            'title' => 'Asset Allocations',
            'categories' => AssetCategory::active()->get(),
            'employees' => Employee::with('user')->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))->get(),
        ]);
    }

    /**
     * Get allocations data for API endpoint.
     */
    public function allocationsIndex(Request $request)
    {
        $allocations = AssetAllocation::with(['asset.category', 'employee.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $allocations->count(),
            'active' => $allocations->where('is_active', true)->count(),
            'returned' => $allocations->where('is_active', false)->count(),
            'overdue' => $allocations->where('is_active', true)
                ->filter(fn ($a) => $a->expected_return_date && $a->expected_return_date < now())
                ->count(),
        ];

        return response()->json([
            'data' => $allocations,
            'stats' => $stats,
        ]);
    }

    public function paginate(Request $request)
    {
        $perPage = $request->get('perPage', 30);
        $query = Asset::with(['category', 'currentAllocation.employee'])
            ->orderBy('created_at', 'desc');

        if ($search = $request->get('search')) {
            $query->where('asset_tag', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        return response()->json($query->paginate($perPage));
    }

    public function stats()
    {
        return response()->json([
            'total' => Asset::count(),
            'available' => Asset::available()->count(),
            'allocated' => Asset::allocated()->count(),
            'maintenance' => Asset::where('status', Asset::STATUS_MAINTENANCE)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

        $asset = Asset::create(array_merge($validated, [
            'asset_tag' => Asset::generateAssetTag(),
            'status' => Asset::STATUS_AVAILABLE,
        ]));

        return response()->json(['message' => 'Asset created', 'asset' => $asset], 201);
    }

    public function allocate(Request $request, int $id)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'allocated_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after:allocated_date',
            'allocation_notes' => 'nullable|string',
            'condition_on_allocation' => 'required|in:new,good,fair,poor',
        ]);

        $asset = Asset::findOrFail($id);

        if (! $asset->canBeAllocated()) {
            return response()->json(['message' => 'Asset cannot be allocated'], 422);
        }

        DB::beginTransaction();
        try {
            AssetAllocation::create(array_merge($validated, [
                'asset_id' => $asset->id,
                'allocated_by' => $request->user()->id,
                'is_active' => true,
            ]));

            $asset->update(['status' => Asset::STATUS_ALLOCATED]);

            DB::commit();

            return response()->json(['message' => 'Asset allocated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to allocate asset'], 500);
        }
    }

    public function returnAsset(Request $request, int $id)
    {
        $validated = $request->validate([
            'returned_date' => 'required|date',
            'return_notes' => 'nullable|string',
            'condition_on_return' => 'required|in:new,good,fair,poor,damaged',
        ]);

        $allocation = AssetAllocation::findOrFail($id);

        if (! $allocation->canBeReturned()) {
            return response()->json(['message' => 'Allocation cannot be returned'], 422);
        }

        DB::beginTransaction();
        try {
            $allocation->update(array_merge($validated, [
                'is_active' => false,
                'returned_to' => $request->user()->id,
            ]));

            $allocation->asset->update(['status' => Asset::STATUS_AVAILABLE]);

            DB::commit();

            return response()->json(['message' => 'Asset returned successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to return asset'], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $asset->update($validated);

        return response()->json(['message' => 'Asset updated', 'asset' => $asset]);
    }

    public function destroy(int $id)
    {
        $asset = Asset::findOrFail($id);

        if ($asset->isAllocated()) {
            return response()->json(['message' => 'Cannot delete allocated asset'], 422);
        }

        $asset->delete();

        return response()->json(['message' => 'Asset deleted']);
    }
}
