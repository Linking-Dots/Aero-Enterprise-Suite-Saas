<?php

namespace Aero\HRM\Http\Controllers\Expense;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmExpenseCategoryController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(): Response
    {
        return Inertia::render('HRM/Expenses/Categories/Index', [
            'categories' => HrmExpenseCategory::withCount('items')->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'unique:hrm_expense_categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data) {
            $cat = HrmExpenseCategory::create($data);
            $this->audit->log(event: 'EXPENSE_CATEGORY_CREATED', action: 'create', subject: $cat, description: "Created expense category: {$cat->name}");
        });

        return back()->with('success', 'Category created.');
    }

    public function update(Request $r, HrmExpenseCategory $category): RedirectResponse
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'unique:hrm_expense_categories,name,'.$category->id],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]);

        DB::transaction(function () use ($category, $data) {
            $category->update($data);
            $this->audit->log(event: 'EXPENSE_CATEGORY_UPDATED', action: 'update', subject: $category, description: "Updated expense category: {$category->name}");
        });

        return back()->with('success', 'Category updated.');
    }

    public function destroy(HrmExpenseCategory $category): RedirectResponse
    {
        abort_if($category->items()->exists(), 422, 'Category has expense items — reassign them first.');

        DB::transaction(function () use ($category) {
            $name = $category->name;
            $category->delete();
            $this->audit->log(event: 'EXPENSE_CATEGORY_DELETED', action: 'delete', subject: $category, description: "Deleted expense category: {$name}");
        });

        return back()->with('success', 'Category deleted.');
    }
}
