<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\StoreCategoryRequest;
use Aero\HRM\Models\TrainingCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TrainingCategoryController extends Controller
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('hrmac', 'hrm.training.training-programs.view');

        $categories = TrainingCategory::withCount('courses')
            ->when($request->search, fn ($q, string $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('HRM/Training/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request): void {
            /** @var TrainingCategory $category */
            $category = TrainingCategory::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => $request->user()->id,
            ]);

            $this->audit->log(
                event: TrainingAuditEvents::COURSE_CREATED,
                action: 'created',
                subject: $category,
                description: "Training category '{$category->name}' created.",
            );
        });

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, TrainingCategory $category): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.training.training-programs.update');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:16'],
            'is_active' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated, $category): void {
            if (isset($validated['name'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $category->update($validated);

            $this->audit->log(
                event: TrainingAuditEvents::COURSE_UPDATED,
                action: 'updated',
                subject: $category,
                description: "Training category '{$category->name}' updated.",
            );
        });

        return back()->with('success', 'Category updated.');
    }

    public function destroy(TrainingCategory $category): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.training.training-programs.delete');

        DB::transaction(function () use ($category): void {
            $category->delete();

            $this->audit->log(
                event: TrainingAuditEvents::COURSE_DELETED,
                action: 'deleted',
                subject: $category,
                description: "Training category '{$category->name}' deleted.",
            );
        });

        return back()->with('success', 'Category deleted.');
    }
}
