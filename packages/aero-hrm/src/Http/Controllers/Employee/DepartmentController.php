<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\StoreDepartmentRequest;
use Aero\HRM\Http\Requests\UpdateDepartmentRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $request): Response
    {
        $this->authorize('hrm.org-structure.departments.view');

        $departments = Department::query()
            ->with(['parent:id,name', 'head.user:id,name'])
            ->withCount('children')
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/OrgStructure/Departments/Index', [
            'departments' => $departments,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('hrm.org-structure.departments.edit');

        return Inertia::render('HRM/OrgStructure/Departments/Create', $this->formProps());
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $dept = Department::create($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_CREATED->value,
            action: 'created',
            subject: $dept,
            description: "Department {$dept->name} created",
            after: $dept->only(['name', 'parent_id']),
        );

        return redirect()->route('hrm.org.departments.index')->with('success', 'Department created.');
    }

    public function show(Department|int|string $department): Response
    {
        $this->authorize('hrm.org-structure.departments.view');
        if (! $department instanceof Department) {
            $department = Department::findOrFail($department);
        }
        $department->load(['parent', 'children', 'head.user']);

        return Inertia::render('HRM/OrgStructure/Departments/Show', [
            'department' => $department,
        ]);
    }

    public function edit(Department|int|string $department): Response
    {
        $this->authorize('hrm.org-structure.departments.edit');
        if (! $department instanceof Department) {
            $department = Department::findOrFail($department);
        }

        return Inertia::render('HRM/OrgStructure/Departments/Edit', array_merge($this->formProps(), [
            'department' => $department->load(['parent', 'head.user']),
        ]));
    }

    public function update(UpdateDepartmentRequest $request, Department|int|string $department): RedirectResponse
    {
        if (! $department instanceof Department) {
            $department = Department::findOrFail($department);
        }
        $before = $department->only(['name', 'parent_id', 'head_employee_id']);
        $department->update($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_UPDATED->value,
            action: 'updated',
            subject: $department,
            description: "Department {$department->name} updated",
            before: $before,
            after: $department->only(['name', 'parent_id', 'head_employee_id']),
        );

        return redirect()->route('hrm.org.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department|int|string $department): RedirectResponse
    {
        $this->authorize('hrm.org-structure.departments.edit');
        if (! $department instanceof Department) {
            $department = Department::findOrFail($department);
        }

        if ($department->children()->exists()) {
            return back()->withErrors(['department' => 'Cannot delete a department that has children.']);
        }

        $department->delete();

        $this->audit->log(
            event: AuditEventType::RECORD_DELETED->value,
            action: 'deleted',
            subject: $department,
            description: "Department {$department->name} deleted",
        );

        return redirect()->route('hrm.org.departments.index')->with('success', 'Department deleted.');
    }

    public function orgChart(Request $request): JsonResponse|Response
    {
        $this->authorize('hrm.org-structure.departments.view');

        $tree = Department::query()
            ->whereNull('parent_id')
            ->with(['children.children.children', 'head.user:id,name'])
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json($tree);
        }

        return Inertia::render('HRM/OrgStructure/Departments/OrgChart', [
            'tree' => $tree,
        ]);
    }

    private function formProps(): array
    {
        return [
            'parents' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'heads' => Employee::query()
                ->with('user:id,name')
                ->select('id', 'user_id', 'employee_code')
                ->orderBy('employee_code')
                ->get()
                ->map(fn ($e) => ['id' => $e->id, 'label' => "{$e->employee_code} — ".($e->user?->name ?? '—')]),
        ];
    }
}
