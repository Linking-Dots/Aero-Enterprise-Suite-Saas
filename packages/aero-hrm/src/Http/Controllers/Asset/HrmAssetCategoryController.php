<?php

namespace Aero\HRM\Http\Controllers\Asset;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmAssetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmAssetCategoryController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(): Response
    {
        return Inertia::render('HRM/Assets/Categories/Index', [
            'categories' => HrmAssetCategory::withCount('assets')->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:100', 'unique:hrm_asset_categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        $cat = DB::transaction(function () use ($data) {
            $cat = HrmAssetCategory::create($data);
            $this->audit->log(event: 'ASSET_CATEGORY_CREATED', action: 'create', subject: $cat, description: "Created category: {$cat->name}");

            return $cat;
        });

        return back()->with('success', 'Category created.');
    }

    public function update(Request $r, HrmAssetCategory $category): RedirectResponse
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:100', 'unique:hrm_asset_categories,name,'.$category->id],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]);

        DB::transaction(function () use ($category, $data) {
            $category->update($data);
            $this->audit->log(event: 'ASSET_CATEGORY_UPDATED', action: 'update', subject: $category, description: "Updated category: {$category->name}");
        });

        return back()->with('success', 'Category updated.');
    }

    public function destroy(HrmAssetCategory $category): RedirectResponse
    {
        abort_if($category->assets()->exists(), 422, 'Category has assets — delete or reassign them first.');

        DB::transaction(function () use ($category) {
            $name = $category->name;
            $category->delete();
            $this->audit->log(event: 'ASSET_CATEGORY_DELETED', action: 'delete', subject: $category, description: "Deleted category: {$name}");
        });

        return back()->with('success', 'Category deleted.');
    }
}
