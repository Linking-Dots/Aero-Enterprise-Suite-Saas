# Plan H-2 — Org Structure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the foundational organization-structure surface — Departments (CRUD + org chart), Designations (CRUD), Grades (CRUD), and Work Locations (CRUD) — fully guarded by HRMAC and audited.

**Architecture:** Each org-structure entity owns a single Inertia controller (`DepartmentController`, `DesignationController`, `GradeController`, `WorkLocationController`). Departments support a self-referencing `parent_id` so the controller can return a recursive tree (`orgChart` endpoint). All four models extend `TenantModel`. Inline-modal create/edit for Designations/Grades/WorkLocations keeps the surface compact; Departments use full Create/Edit pages because of the org-chart picker.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## Task 1 — Migrations & Models for the four entities

**Files:**
- Create: `packages/aero-hrm/database/migrations/2026_05_17_010001_create_grades_table.php`
- Create: `packages/aero-hrm/database/migrations/2026_05_17_010002_create_work_locations_table.php`
- Create: `packages/aero-hrm/database/migrations/2026_05_17_010003_add_parent_id_to_departments_table.php`
- Create: `packages/aero-hrm/src/Models/Grade.php`
- Create: `packages/aero-hrm/src/Models/WorkLocation.php`
- Modify: `packages/aero-hrm/src/Models/Department.php`
- Modify: `packages/aero-hrm/src/Models/Designation.php`

- [ ] Grades migration:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $t) {
            $t->id();
            $t->string('name', 64);
            $t->string('code', 16)->nullable();
            $t->decimal('min_salary', 12, 2)->nullable();
            $t->decimal('max_salary', 12, 2)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['name']);
        });
    }
    public function down(): void { Schema::dropIfExists('grades'); }
};
```

- [ ] Work Locations migration:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_locations', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('type', 32)->default('office'); // office, remote, hybrid, site
            $t->string('address')->nullable();
            $t->string('city', 80)->nullable();
            $t->string('country', 80)->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('work_locations'); }
};
```

- [ ] Departments parent_id migration:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $t) {
            if (!Schema::hasColumn('departments', 'parent_id')) {
                $t->foreignId('parent_id')->nullable()->after('id')
                  ->constrained('departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('departments', 'head_employee_id')) {
                $t->foreignId('head_employee_id')->nullable()->after('parent_id')
                  ->constrained('employees')->nullOnDelete();
            }
        });
    }
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('parent_id');
            $t->dropConstrainedForeignId('head_employee_id');
        });
    }
};
```

- [ ] `Grade.php`:
```php
<?php
namespace Aero\HRM\Models;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends TenantModel
{
    use SoftDeletes;
    protected $table = 'grades';
    protected $fillable = ['name', 'code', 'min_salary', 'max_salary', 'is_active'];
    protected $casts = ['min_salary' => 'decimal:2', 'max_salary' => 'decimal:2', 'is_active' => 'boolean'];
}
```

- [ ] `WorkLocation.php`:
```php
<?php
namespace Aero\HRM\Models;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkLocation extends TenantModel
{
    use SoftDeletes;
    protected $table = 'work_locations';
    protected $fillable = ['name', 'type', 'address', 'city', 'country', 'latitude', 'longitude', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
}
```

- [ ] Extend `Department.php` with parent/children/head relationships:
```php
public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(self::class, 'parent_id');
}

public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(self::class, 'parent_id');
}

public function head(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Employee::class, 'head_employee_id');
}

// add 'parent_id', 'head_employee_id' to $fillable
```

- [ ] Commit:
```bash
git add packages/aero-hrm/database/migrations/2026_05_17_010001_create_grades_table.php \
       packages/aero-hrm/database/migrations/2026_05_17_010002_create_work_locations_table.php \
       packages/aero-hrm/database/migrations/2026_05_17_010003_add_parent_id_to_departments_table.php \
       packages/aero-hrm/src/Models/Grade.php \
       packages/aero-hrm/src/Models/WorkLocation.php \
       packages/aero-hrm/src/Models/Department.php
git commit -m "feat(aero-hrm): grades, work locations, hierarchical departments"
```

---

## Task 2 — Form Requests for the four entities

**Files:**
- Create: `packages/aero-hrm/src/Http/Requests/StoreDepartmentRequest.php`
- Create: `packages/aero-hrm/src/Http/Requests/UpdateDepartmentRequest.php`
- Create: `packages/aero-hrm/src/Http/Requests/DesignationRequest.php`
- Create: `packages/aero-hrm/src/Http/Requests/GradeRequest.php`
- Create: `packages/aero-hrm/src/Http/Requests/WorkLocationRequest.php`

- [ ] `StoreDepartmentRequest`:
```php
<?php
namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.org-structure.departments.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:120', Rule::unique('departments', 'name')],
            'parent_id'        => ['nullable', 'integer', 'exists:departments,id'],
            'head_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'description'      => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

- [ ] `UpdateDepartmentRequest`:
```php
<?php
namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.org-structure.departments.edit') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('department')?->id;
        return [
            'name'             => ['required', 'string', 'max:120', Rule::unique('departments', 'name')->ignore($id)],
            'parent_id'        => ['nullable', 'integer', 'exists:departments,id', "different:{$id}"],
            'head_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'description'      => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

- [ ] `DesignationRequest` (covers both store and update via `isMethod`):
```php
<?php
namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.org-structure.designations.edit') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('designation')?->id;
        return [
            'title'         => ['required', 'string', 'max:120', Rule::unique('designations', 'title')->ignore($id)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'grade_id'      => ['nullable', 'integer', 'exists:grades,id'],
            'description'   => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

- [ ] `GradeRequest`:
```php
<?php
namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.org-structure.grades.edit') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('grade')?->id;
        return [
            'name'       => ['required', 'string', 'max:64', Rule::unique('grades', 'name')->ignore($id)],
            'code'       => ['nullable', 'string', 'max:16'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'gte:min_salary'],
            'is_active'  => ['boolean'],
        ];
    }
}
```

- [ ] `WorkLocationRequest`:
```php
<?php
namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.org-structure.work-locations.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:120'],
            'type'      => ['required', Rule::in(['office', 'remote', 'hybrid', 'site'])],
            'address'   => ['nullable', 'string', 'max:255'],
            'city'      => ['nullable', 'string', 'max:80'],
            'country'   => ['nullable', 'string', 'max:80'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ];
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Requests/StoreDepartmentRequest.php \
       packages/aero-hrm/src/Http/Requests/UpdateDepartmentRequest.php \
       packages/aero-hrm/src/Http/Requests/DesignationRequest.php \
       packages/aero-hrm/src/Http/Requests/GradeRequest.php \
       packages/aero-hrm/src/Http/Requests/WorkLocationRequest.php
git commit -m "feat(aero-hrm): form requests for org-structure entities"
```

---

## Task 3 — DepartmentController rewrite (with org chart)

**Files:**
- Modify: `packages/aero-hrm/src/Http/Controllers/Employee/DepartmentController.php`

- [ ] Replace controller body:
```php
<?php

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
            'filters'     => $request->only('search'),
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

    public function show(Department $department): Response
    {
        $this->authorize('hrm.org-structure.departments.view');

        $department->load(['parent', 'children', 'head.user']);

        return Inertia::render('HRM/OrgStructure/Departments/Show', [
            'department' => $department,
        ]);
    }

    public function edit(Department $department): Response
    {
        $this->authorize('hrm.org-structure.departments.edit');
        return Inertia::render('HRM/OrgStructure/Departments/Edit', array_merge($this->formProps(), [
            'department' => $department->load(['parent', 'head.user']),
        ]));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
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

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('hrm.org-structure.departments.edit');

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

    public function orgChart(): JsonResponse|Response
    {
        $this->authorize('hrm.org-structure.departments.view');

        $tree = Department::query()
            ->whereNull('parent_id')
            ->with(['children.children.children', 'head.user:id,name'])
            ->orderBy('name')
            ->get();

        if (request()->wantsJson()) {
            return response()->json($tree);
        }

        return Inertia::render('HRM/OrgStructure/Departments/OrgChart', [
            'tree' => $tree,
        ]);
    }

    private function formProps(): array
    {
        return [
            'parents'  => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'heads'    => Employee::query()->with('user:id,name')->select('id', 'user_id', 'employee_code')->orderBy('employee_code')->get()
                ->map(fn ($e) => ['id' => $e->id, 'label' => "{$e->employee_code} — ".($e->user?->name ?? '—')]),
        ];
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Controllers/Employee/DepartmentController.php
git commit -m "feat(aero-hrm): DepartmentController CRUD + org chart endpoint"
```

---

## Task 4 — Designation / Grade / WorkLocation controllers

**Files:**
- Modify: `packages/aero-hrm/src/Http/Controllers/Employee/DesignationController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/OrgStructure/GradeController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/OrgStructure/WorkLocationController.php`

- [ ] `DesignationController`:
```php
<?php

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\DesignationRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignationController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $request): Response
    {
        $this->authorize('hrm.org-structure.designations.view');

        return Inertia::render('HRM/OrgStructure/Designations/Index', [
            'designations' => Designation::query()
                ->with(['department:id,name', 'grade:id,name'])
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->orderBy('title')
                ->paginate(20)
                ->withQueryString(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'grades'      => Grade::select('id', 'name')->orderBy('name')->get(),
            'filters'     => $request->only('search'),
        ]);
    }

    public function store(DesignationRequest $request): RedirectResponse
    {
        $designation = Designation::create($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_CREATED->value,
            action: 'created',
            subject: $designation,
            description: "Designation {$designation->title} created",
        );

        return back()->with('success', 'Designation created.');
    }

    public function update(DesignationRequest $request, Designation $designation): RedirectResponse
    {
        $before = $designation->only(['title', 'department_id', 'grade_id']);
        $designation->update($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_UPDATED->value,
            action: 'updated',
            subject: $designation,
            description: "Designation {$designation->title} updated",
            before: $before,
            after: $designation->only(['title', 'department_id', 'grade_id']),
        );

        return back()->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $this->authorize('hrm.org-structure.designations.edit');
        $designation->delete();

        $this->audit->log(
            event: AuditEventType::RECORD_DELETED->value,
            action: 'deleted',
            subject: $designation,
            description: "Designation {$designation->title} deleted",
        );

        return back()->with('success', 'Designation deleted.');
    }
}
```

- [ ] `GradeController`:
```php
<?php

namespace Aero\HRM\Http\Controllers\OrgStructure;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\GradeRequest;
use Aero\HRM\Models\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $request): Response
    {
        $this->authorize('hrm.org-structure.grades.view');

        return Inertia::render('HRM/OrgStructure/Grades/Index', [
            'grades' => Grade::query()
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(GradeRequest $request): RedirectResponse
    {
        $grade = Grade::create($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_CREATED->value,
            action: 'created',
            subject: $grade,
            description: "Grade {$grade->name} created",
        );

        return back()->with('success', 'Grade created.');
    }

    public function update(GradeRequest $request, Grade $grade): RedirectResponse
    {
        $before = $grade->only(['name', 'min_salary', 'max_salary', 'is_active']);
        $grade->update($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_UPDATED->value,
            action: 'updated',
            subject: $grade,
            description: "Grade {$grade->name} updated",
            before: $before,
            after: $grade->only(['name', 'min_salary', 'max_salary', 'is_active']),
        );

        return back()->with('success', 'Grade updated.');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $this->authorize('hrm.org-structure.grades.edit');
        $grade->delete();

        $this->audit->log(
            event: AuditEventType::RECORD_DELETED->value,
            action: 'deleted',
            subject: $grade,
            description: "Grade {$grade->name} deleted",
        );

        return back()->with('success', 'Grade deleted.');
    }
}
```

- [ ] `WorkLocationController`:
```php
<?php

namespace Aero\HRM\Http\Controllers\OrgStructure;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\WorkLocationRequest;
use Aero\HRM\Models\WorkLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkLocationController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $request): Response
    {
        $this->authorize('hrm.org-structure.work-locations.view');

        return Inertia::render('HRM/OrgStructure/WorkLocations/Index', [
            'locations' => WorkLocation::query()
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'types'   => ['office', 'remote', 'hybrid', 'site'],
            'filters' => $request->only('search'),
        ]);
    }

    public function store(WorkLocationRequest $request): RedirectResponse
    {
        $loc = WorkLocation::create($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_CREATED->value,
            action: 'created',
            subject: $loc,
            description: "Work location {$loc->name} created",
        );

        return back()->with('success', 'Work location created.');
    }

    public function update(WorkLocationRequest $request, WorkLocation $workLocation): RedirectResponse
    {
        $before = $workLocation->only(['name', 'type', 'city', 'country', 'is_active']);
        $workLocation->update($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_UPDATED->value,
            action: 'updated',
            subject: $workLocation,
            description: "Work location {$workLocation->name} updated",
            before: $before,
            after: $workLocation->only(['name', 'type', 'city', 'country', 'is_active']),
        );

        return back()->with('success', 'Work location updated.');
    }

    public function destroy(WorkLocation $workLocation): RedirectResponse
    {
        $this->authorize('hrm.org-structure.work-locations.edit');
        $workLocation->delete();

        $this->audit->log(
            event: AuditEventType::RECORD_DELETED->value,
            action: 'deleted',
            subject: $workLocation,
            description: "Work location {$workLocation->name} deleted",
        );

        return back()->with('success', 'Work location deleted.');
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Controllers/Employee/DesignationController.php \
       packages/aero-hrm/src/Http/Controllers/OrgStructure/GradeController.php \
       packages/aero-hrm/src/Http/Controllers/OrgStructure/WorkLocationController.php
git commit -m "feat(aero-hrm): designation/grade/work-location controllers"
```

---

## Task 5 — Routes + HRMAC config

**Files:**
- Modify: `packages/aero-hrm/routes/tenant.php`
- Modify: `packages/aero-hrm/config/module.php`

- [ ] Append routes:
```php
use Aero\HRM\Http\Controllers\Employee\DepartmentController;
use Aero\HRM\Http\Controllers\Employee\DesignationController;
use Aero\HRM\Http\Controllers\OrgStructure\GradeController;
use Aero\HRM\Http\Controllers\OrgStructure\WorkLocationController;

Route::middleware(['auth', 'tenant'])->prefix('hrm/org-structure')->name('hrm.org.')->group(function () {

    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/',           [DepartmentController::class, 'index'])->middleware('hrmac:hrm.org-structure.departments.view')->name('index');
        Route::get('/chart',      [DepartmentController::class, 'orgChart'])->middleware('hrmac:hrm.org-structure.departments.view')->name('chart');
        Route::get('/create',     [DepartmentController::class, 'create'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('create');
        Route::post('/',          [DepartmentController::class, 'store'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('store');
        Route::get('/{department}',      [DepartmentController::class, 'show'])->middleware('hrmac:hrm.org-structure.departments.view')->name('show');
        Route::get('/{department}/edit', [DepartmentController::class, 'edit'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('edit');
        Route::put('/{department}',      [DepartmentController::class, 'update'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('update');
        Route::delete('/{department}',   [DepartmentController::class, 'destroy'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('destroy');
    });

    Route::prefix('designations')->name('designations.')->group(function () {
        Route::get('/',           [DesignationController::class, 'index'])->middleware('hrmac:hrm.org-structure.designations.view')->name('index');
        Route::post('/',          [DesignationController::class, 'store'])->middleware('hrmac:hrm.org-structure.designations.edit')->name('store');
        Route::put('/{designation}',     [DesignationController::class, 'update'])->middleware('hrmac:hrm.org-structure.designations.edit')->name('update');
        Route::delete('/{designation}',  [DesignationController::class, 'destroy'])->middleware('hrmac:hrm.org-structure.designations.edit')->name('destroy');
    });

    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/',           [GradeController::class, 'index'])->middleware('hrmac:hrm.org-structure.grades.view')->name('index');
        Route::post('/',          [GradeController::class, 'store'])->middleware('hrmac:hrm.org-structure.grades.edit')->name('store');
        Route::put('/{grade}',    [GradeController::class, 'update'])->middleware('hrmac:hrm.org-structure.grades.edit')->name('update');
        Route::delete('/{grade}', [GradeController::class, 'destroy'])->middleware('hrmac:hrm.org-structure.grades.edit')->name('destroy');
    });

    Route::prefix('work-locations')->name('work-locations.')->group(function () {
        Route::get('/',           [WorkLocationController::class, 'index'])->middleware('hrmac:hrm.org-structure.work-locations.view')->name('index');
        Route::post('/',          [WorkLocationController::class, 'store'])->middleware('hrmac:hrm.org-structure.work-locations.edit')->name('store');
        Route::put('/{workLocation}',    [WorkLocationController::class, 'update'])->middleware('hrmac:hrm.org-structure.work-locations.edit')->name('update');
        Route::delete('/{workLocation}', [WorkLocationController::class, 'destroy'])->middleware('hrmac:hrm.org-structure.work-locations.edit')->name('destroy');
    });
});
```

- [ ] Add HRMAC entries in `config/module.php` under a new `org-structure` submodule:
```php
'org-structure' => [
    'label' => 'Org Structure',
    'components' => [
        'departments'    => ['actions' => ['view', 'edit']],
        'designations'   => ['actions' => ['view', 'edit']],
        'grades'         => ['actions' => ['view', 'edit']],
        'work-locations' => ['actions' => ['view', 'edit']],
    ],
],
```

- [ ] Clear HRMAC cache:
```bash
php artisan hrmac:cache:clear
```

- [ ] Commit:
```bash
git add packages/aero-hrm/routes/tenant.php packages/aero-hrm/config/module.php
git commit -m "feat(aero-hrm): wire org-structure routes + HRMAC config"
```

---

## Task 6 — React pages: Departments list + create + edit + org chart

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Departments/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Departments/Create.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Departments/Edit.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Departments/OrgChart.jsx`

- [ ] `Index.jsx` (list + org-chart toggle):
```jsx
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Input, Button, Pagination } from '@aero/ui';

export default function DepartmentsIndex({ departments, filters }) {
  const [search, setSearch] = useState(filters.search ?? '');

  const apply = () => router.get(route('hrm.org.departments.index'), { search: search || undefined }, { preserveState: true, replace: true });

  return (
    <>
      <Head title="Departments" />
      <Card>
        <CardHeader className="flex justify-between items-center">
          <h1 className="text-2xl font-semibold">Departments</h1>
          <div className="flex gap-2">
            <Button as={Link} href={route('hrm.org.departments.chart')} variant="flat">Org Chart</Button>
            <Button as={Link} href={route('hrm.org.departments.create')} color="primary">New Department</Button>
          </div>
        </CardHeader>
        <CardBody className="gap-4">
          <Input label="Search" value={search} onValueChange={setSearch} onBlur={apply} className="max-w-sm" />
          <Table aria-label="Departments">
            <TableHeader>
              <TableColumn>Name</TableColumn>
              <TableColumn>Parent</TableColumn>
              <TableColumn>Head</TableColumn>
              <TableColumn>Children</TableColumn>
              <TableColumn> </TableColumn>
            </TableHeader>
            <TableBody emptyContent="No departments yet.">
              {departments.data.map(d => (
                <TableRow key={d.id}>
                  <TableCell>{d.name}</TableCell>
                  <TableCell>{d.parent?.name ?? '—'}</TableCell>
                  <TableCell>{d.head?.user?.name ?? '—'}</TableCell>
                  <TableCell>{d.children_count}</TableCell>
                  <TableCell>
                    <Button size="sm" as={Link} href={route('hrm.org.departments.edit', d.id)} variant="light">Edit</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          <div className="flex justify-end">
            <Pagination total={departments.last_page} page={departments.current_page}
              onChange={(p) => router.get(route('hrm.org.departments.index'), { ...filters, page: p }, { preserveState: true })} />
          </div>
        </CardBody>
      </Card>
    </>
  );
}
DepartmentsIndex.layout = page => <App title="Departments">{page}</App>;
```

- [ ] `Create.jsx`:
```jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, CardBody, Input, Select, SelectItem, Textarea, Button } from '@aero/ui';

export default function CreateDepartment({ parents, heads }) {
  const { data, setData, post, processing, errors } = useForm({
    name: '', parent_id: null, head_employee_id: null, description: '',
  });
  const submit = (e) => { e.preventDefault(); post(route('hrm.org.departments.store')); };

  return (
    <>
      <Head title="New Department" />
      <form onSubmit={submit}>
        <Card><CardBody className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input label="Name" value={data.name} onValueChange={(v) => setData('name', v)} errorMessage={errors.name} />
          <Select label="Parent Department" selectedKeys={data.parent_id ? [String(data.parent_id)] : []}
                  onSelectionChange={(k) => setData('parent_id', [...k][0] ?? null)} errorMessage={errors.parent_id}>
            {parents.map(p => <SelectItem key={p.id}>{p.name}</SelectItem>)}
          </Select>
          <Select label="Department Head" selectedKeys={data.head_employee_id ? [String(data.head_employee_id)] : []}
                  onSelectionChange={(k) => setData('head_employee_id', [...k][0] ?? null)} errorMessage={errors.head_employee_id}>
            {heads.map(h => <SelectItem key={h.id}>{h.label}</SelectItem>)}
          </Select>
          <Textarea label="Description" value={data.description} onValueChange={(v) => setData('description', v)} errorMessage={errors.description} className="md:col-span-2" />
          <div className="md:col-span-2 flex justify-end">
            <Button type="submit" color="primary" isLoading={processing}>Create Department</Button>
          </div>
        </CardBody></Card>
      </form>
    </>
  );
}
CreateDepartment.layout = page => <App title="New Department">{page}</App>;
```

- [ ] `Edit.jsx` (mirror of Create with `put` and preloaded `department`):
```jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, CardBody, Input, Select, SelectItem, Textarea, Button } from '@aero/ui';

export default function EditDepartment({ department, parents, heads }) {
  const { data, setData, put, processing, errors } = useForm({
    name: department.name ?? '',
    parent_id: department.parent_id,
    head_employee_id: department.head_employee_id,
    description: department.description ?? '',
  });
  const submit = (e) => { e.preventDefault(); put(route('hrm.org.departments.update', department.id)); };

  return (
    <>
      <Head title={`Edit ${department.name}`} />
      <form onSubmit={submit}>
        <Card><CardBody className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input label="Name" value={data.name} onValueChange={(v) => setData('name', v)} errorMessage={errors.name} />
          <Select label="Parent Department" selectedKeys={data.parent_id ? [String(data.parent_id)] : []}
                  onSelectionChange={(k) => setData('parent_id', [...k][0] ?? null)} errorMessage={errors.parent_id}>
            {parents.filter(p => p.id !== department.id).map(p => <SelectItem key={p.id}>{p.name}</SelectItem>)}
          </Select>
          <Select label="Department Head" selectedKeys={data.head_employee_id ? [String(data.head_employee_id)] : []}
                  onSelectionChange={(k) => setData('head_employee_id', [...k][0] ?? null)} errorMessage={errors.head_employee_id}>
            {heads.map(h => <SelectItem key={h.id}>{h.label}</SelectItem>)}
          </Select>
          <Textarea label="Description" value={data.description} onValueChange={(v) => setData('description', v)} errorMessage={errors.description} className="md:col-span-2" />
          <div className="md:col-span-2 flex justify-end">
            <Button type="submit" color="primary" isLoading={processing}>Save Changes</Button>
          </div>
        </CardBody></Card>
      </form>
    </>
  );
}
EditDepartment.layout = page => <App title="Edit Department">{page}</App>;
```

- [ ] `OrgChart.jsx` (recursive tree):
```jsx
import { Head } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, CardBody } from '@aero/ui';

function Node({ d }) {
  return (
    <li className="ml-4 list-disc">
      <div>
        <span className="font-medium">{d.name}</span>
        {d.head?.user?.name && <span className="text-default-500"> · {d.head.user.name}</span>}
      </div>
      {d.children?.length > 0 && (
        <ul>{d.children.map(c => <Node key={c.id} d={c} />)}</ul>
      )}
    </li>
  );
}

export default function OrgChart({ tree }) {
  return (
    <>
      <Head title="Org Chart" />
      <Card><CardBody>
        <h1 className="text-2xl font-semibold mb-4">Organization Chart</h1>
        <ul className="space-y-2">{tree.map(d => <Node key={d.id} d={d} />)}</ul>
      </CardBody></Card>
    </>
  );
}
OrgChart.layout = page => <App title="Org Chart">{page}</App>;
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Departments/
git commit -m "feat(aero-ui): department list/create/edit/org-chart pages"
```

---

## Task 7 — React modal pages: Designations / Grades / Work Locations

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Designations/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Grades/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/OrgStructure/WorkLocations/Index.jsx`

- [ ] `Designations/Index.jsx`:
```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Button, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, Input, Select, SelectItem, useDisclosure, Pagination } from '@aero/ui';

export default function DesignationsIndex({ designations, departments, grades, filters }) {
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [editing, setEditing] = useState(null);
  const { data, setData, post, put, processing, errors, reset } = useForm({
    title: '', department_id: null, grade_id: null, description: '',
  });

  const openCreate = () => { reset(); setEditing(null); onOpen(); };
  const openEdit = (d) => { setEditing(d); setData({ title: d.title, department_id: d.department_id, grade_id: d.grade_id, description: d.description ?? '' }); onOpen(); };
  const submit = (e) => {
    e.preventDefault();
    if (editing) put(route('hrm.org.designations.update', editing.id), { onSuccess: onClose });
    else post(route('hrm.org.designations.store'), { onSuccess: onClose });
  };

  return (
    <>
      <Head title="Designations" />
      <Card>
        <CardHeader className="flex justify-between">
          <h1 className="text-2xl font-semibold">Designations</h1>
          <Button color="primary" onPress={openCreate}>New Designation</Button>
        </CardHeader>
        <CardBody>
          <Table aria-label="Designations">
            <TableHeader>
              <TableColumn>Title</TableColumn>
              <TableColumn>Department</TableColumn>
              <TableColumn>Grade</TableColumn>
              <TableColumn> </TableColumn>
            </TableHeader>
            <TableBody emptyContent="No designations.">
              {designations.data.map(d => (
                <TableRow key={d.id}>
                  <TableCell>{d.title}</TableCell>
                  <TableCell>{d.department?.name ?? '—'}</TableCell>
                  <TableCell>{d.grade?.name ?? '—'}</TableCell>
                  <TableCell>
                    <Button size="sm" variant="light" onPress={() => openEdit(d)}>Edit</Button>
                    <Button size="sm" variant="light" color="danger"
                      onPress={() => router.delete(route('hrm.org.designations.destroy', d.id))}>Delete</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          <div className="flex justify-end mt-4">
            <Pagination total={designations.last_page} page={designations.current_page}
              onChange={(p) => router.get(route('hrm.org.designations.index'), { ...filters, page: p }, { preserveState: true })} />
          </div>
        </CardBody>
      </Card>

      <Modal isOpen={isOpen} onClose={onClose}>
        <ModalContent>
          <form onSubmit={submit}>
            <ModalHeader>{editing ? 'Edit Designation' : 'New Designation'}</ModalHeader>
            <ModalBody className="gap-4">
              <Input label="Title" value={data.title} onValueChange={(v) => setData('title', v)} errorMessage={errors.title} />
              <Select label="Department" selectedKeys={data.department_id ? [String(data.department_id)] : []}
                      onSelectionChange={(k) => setData('department_id', [...k][0] ?? null)}>
                {departments.map(d => <SelectItem key={d.id}>{d.name}</SelectItem>)}
              </Select>
              <Select label="Grade" selectedKeys={data.grade_id ? [String(data.grade_id)] : []}
                      onSelectionChange={(k) => setData('grade_id', [...k][0] ?? null)}>
                {grades.map(g => <SelectItem key={g.id}>{g.name}</SelectItem>)}
              </Select>
            </ModalBody>
            <ModalFooter>
              <Button variant="light" onPress={onClose}>Cancel</Button>
              <Button type="submit" color="primary" isLoading={processing}>Save</Button>
            </ModalFooter>
          </form>
        </ModalContent>
      </Modal>
    </>
  );
}
DesignationsIndex.layout = page => <App title="Designations">{page}</App>;
```

- [ ] `Grades/Index.jsx` (same shape — fields: name, code, min_salary, max_salary, is_active toggle):
```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Button, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, Input, Switch, useDisclosure, Pagination } from '@aero/ui';

export default function GradesIndex({ grades, filters }) {
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [editing, setEditing] = useState(null);
  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '', code: '', min_salary: '', max_salary: '', is_active: true,
  });

  const openCreate = () => { reset(); setEditing(null); onOpen(); };
  const openEdit = (g) => { setEditing(g); setData({ ...g }); onOpen(); };
  const submit = (e) => {
    e.preventDefault();
    if (editing) put(route('hrm.org.grades.update', editing.id), { onSuccess: onClose });
    else post(route('hrm.org.grades.store'), { onSuccess: onClose });
  };

  return (
    <>
      <Head title="Grades" />
      <Card>
        <CardHeader className="flex justify-between">
          <h1 className="text-2xl font-semibold">Grades</h1>
          <Button color="primary" onPress={openCreate}>New Grade</Button>
        </CardHeader>
        <CardBody>
          <Table aria-label="Grades">
            <TableHeader>
              <TableColumn>Name</TableColumn>
              <TableColumn>Code</TableColumn>
              <TableColumn>Min Salary</TableColumn>
              <TableColumn>Max Salary</TableColumn>
              <TableColumn>Active</TableColumn>
              <TableColumn> </TableColumn>
            </TableHeader>
            <TableBody emptyContent="No grades.">
              {grades.data.map(g => (
                <TableRow key={g.id}>
                  <TableCell>{g.name}</TableCell>
                  <TableCell>{g.code ?? '—'}</TableCell>
                  <TableCell>{g.min_salary ?? '—'}</TableCell>
                  <TableCell>{g.max_salary ?? '—'}</TableCell>
                  <TableCell>{g.is_active ? 'Yes' : 'No'}</TableCell>
                  <TableCell>
                    <Button size="sm" variant="light" onPress={() => openEdit(g)}>Edit</Button>
                    <Button size="sm" variant="light" color="danger"
                      onPress={() => router.delete(route('hrm.org.grades.destroy', g.id))}>Delete</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          <div className="flex justify-end mt-4">
            <Pagination total={grades.last_page} page={grades.current_page}
              onChange={(p) => router.get(route('hrm.org.grades.index'), { ...filters, page: p }, { preserveState: true })} />
          </div>
        </CardBody>
      </Card>

      <Modal isOpen={isOpen} onClose={onClose}>
        <ModalContent>
          <form onSubmit={submit}>
            <ModalHeader>{editing ? 'Edit Grade' : 'New Grade'}</ModalHeader>
            <ModalBody className="gap-4">
              <Input label="Name" value={data.name} onValueChange={(v) => setData('name', v)} errorMessage={errors.name} />
              <Input label="Code" value={data.code ?? ''} onValueChange={(v) => setData('code', v)} errorMessage={errors.code} />
              <Input type="number" label="Min Salary" value={data.min_salary ?? ''} onValueChange={(v) => setData('min_salary', v)} errorMessage={errors.min_salary} />
              <Input type="number" label="Max Salary" value={data.max_salary ?? ''} onValueChange={(v) => setData('max_salary', v)} errorMessage={errors.max_salary} />
              <Switch isSelected={!!data.is_active} onValueChange={(v) => setData('is_active', v)}>Active</Switch>
            </ModalBody>
            <ModalFooter>
              <Button variant="light" onPress={onClose}>Cancel</Button>
              <Button type="submit" color="primary" isLoading={processing}>Save</Button>
            </ModalFooter>
          </form>
        </ModalContent>
      </Modal>
    </>
  );
}
GradesIndex.layout = page => <App title="Grades">{page}</App>;
```

- [ ] `WorkLocations/Index.jsx` (similar pattern, fields: name, type, address, city, country, lat, lng, is_active):
```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Button, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, Input, Select, SelectItem, Switch, useDisclosure, Pagination } from '@aero/ui';

export default function WorkLocationsIndex({ locations, types, filters }) {
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [editing, setEditing] = useState(null);
  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '', type: 'office', address: '', city: '', country: '', latitude: '', longitude: '', is_active: true,
  });

  const openCreate = () => { reset(); setEditing(null); onOpen(); };
  const openEdit = (l) => { setEditing(l); setData({ ...l }); onOpen(); };
  const submit = (e) => {
    e.preventDefault();
    if (editing) put(route('hrm.org.work-locations.update', editing.id), { onSuccess: onClose });
    else post(route('hrm.org.work-locations.store'), { onSuccess: onClose });
  };

  return (
    <>
      <Head title="Work Locations" />
      <Card>
        <CardHeader className="flex justify-between">
          <h1 className="text-2xl font-semibold">Work Locations</h1>
          <Button color="primary" onPress={openCreate}>New Location</Button>
        </CardHeader>
        <CardBody>
          <Table aria-label="Work locations">
            <TableHeader>
              <TableColumn>Name</TableColumn>
              <TableColumn>Type</TableColumn>
              <TableColumn>City</TableColumn>
              <TableColumn>Country</TableColumn>
              <TableColumn>Active</TableColumn>
              <TableColumn> </TableColumn>
            </TableHeader>
            <TableBody emptyContent="No work locations.">
              {locations.data.map(l => (
                <TableRow key={l.id}>
                  <TableCell>{l.name}</TableCell>
                  <TableCell>{l.type}</TableCell>
                  <TableCell>{l.city ?? '—'}</TableCell>
                  <TableCell>{l.country ?? '—'}</TableCell>
                  <TableCell>{l.is_active ? 'Yes' : 'No'}</TableCell>
                  <TableCell>
                    <Button size="sm" variant="light" onPress={() => openEdit(l)}>Edit</Button>
                    <Button size="sm" variant="light" color="danger"
                      onPress={() => router.delete(route('hrm.org.work-locations.destroy', l.id))}>Delete</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          <div className="flex justify-end mt-4">
            <Pagination total={locations.last_page} page={locations.current_page}
              onChange={(p) => router.get(route('hrm.org.work-locations.index'), { ...filters, page: p }, { preserveState: true })} />
          </div>
        </CardBody>
      </Card>

      <Modal isOpen={isOpen} onClose={onClose}>
        <ModalContent>
          <form onSubmit={submit}>
            <ModalHeader>{editing ? 'Edit Location' : 'New Location'}</ModalHeader>
            <ModalBody className="gap-4">
              <Input label="Name" value={data.name} onValueChange={(v) => setData('name', v)} errorMessage={errors.name} />
              <Select label="Type" selectedKeys={[data.type]} onSelectionChange={(k) => setData('type', [...k][0])}>
                {types.map(t => <SelectItem key={t}>{t}</SelectItem>)}
              </Select>
              <Input label="Address" value={data.address ?? ''} onValueChange={(v) => setData('address', v)} />
              <Input label="City"    value={data.city ?? ''}    onValueChange={(v) => setData('city', v)} />
              <Input label="Country" value={data.country ?? ''} onValueChange={(v) => setData('country', v)} />
              <Input label="Latitude"  value={data.latitude ?? ''}  onValueChange={(v) => setData('latitude', v)} />
              <Input label="Longitude" value={data.longitude ?? ''} onValueChange={(v) => setData('longitude', v)} />
              <Switch isSelected={!!data.is_active} onValueChange={(v) => setData('is_active', v)}>Active</Switch>
            </ModalBody>
            <ModalFooter>
              <Button variant="light" onPress={onClose}>Cancel</Button>
              <Button type="submit" color="primary" isLoading={processing}>Save</Button>
            </ModalFooter>
          </form>
        </ModalContent>
      </Modal>
    </>
  );
}
WorkLocationsIndex.layout = page => <App title="Work Locations">{page}</App>;
```

- [ ] Build the UI bundle:
```bash
npm run build --workspace=packages/aero-ui
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Designations/ \
       packages/aero-ui/resources/js/Pages/HRM/OrgStructure/Grades/ \
       packages/aero-ui/resources/js/Pages/HRM/OrgStructure/WorkLocations/
git commit -m "feat(aero-ui): designation/grade/work-location modal CRUD pages"
```

---

## Task 8 — Feature tests for Departments

**Files:**
- Create: `packages/aero-hrm/tests/Feature/OrgStructure/DepartmentControllerTest.php`

- [ ] Write tests (6 methods):
```php
<?php

namespace Aero\HRM\Tests\Feature\OrgStructure;

use Aero\Core\AeroCoreServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroHrmServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
    }

    private function actingAsHR(array $abilities = ['hrm.org-structure.departments.view', 'hrm.org-structure.departments.edit']): User
    {
        $user = User::factory()->create();
        foreach ($abilities as $a) {
            $user->givePermissionTo($a);
        }
        $this->actingAs($user);
        return $user;
    }

    public function test_index_renders_paginated_departments(): void
    {
        $this->actingAsHR();
        Department::factory()->count(3)->create();

        $this->get(route('hrm.org.departments.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('HRM/OrgStructure/Departments/Index')
                ->has('departments.data', 3));
    }

    public function test_store_creates_department(): void
    {
        $this->actingAsHR();

        $this->post(route('hrm.org.departments.store'), ['name' => 'Engineering'])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', ['name' => 'Engineering']);
    }

    public function test_store_validates_unique_name(): void
    {
        $this->actingAsHR();
        Department::factory()->create(['name' => 'Engineering']);

        $this->post(route('hrm.org.departments.store'), ['name' => 'Engineering'])
            ->assertSessionHasErrors('name');
    }

    public function test_update_modifies_department(): void
    {
        $this->actingAsHR();
        $dept = Department::factory()->create(['name' => 'Old']);

        $this->put(route('hrm.org.departments.update', $dept), ['name' => 'New'])
            ->assertRedirect();

        $this->assertSame('New', $dept->fresh()->name);
    }

    public function test_destroy_blocks_when_children_exist(): void
    {
        $this->actingAsHR();
        $parent = Department::factory()->create();
        Department::factory()->create(['parent_id' => $parent->id]);

        $this->delete(route('hrm.org.departments.destroy', $parent))
            ->assertSessionHasErrors('department');
    }

    public function test_org_chart_returns_tree(): void
    {
        $this->actingAsHR();
        $root  = Department::factory()->create(['name' => 'HQ']);
        $child = Department::factory()->create(['name' => 'Eng', 'parent_id' => $root->id]);

        $this->get(route('hrm.org.departments.chart'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('HRM/OrgStructure/Departments/OrgChart')
                ->where('tree.0.name', 'HQ')
                ->where('tree.0.children.0.name', 'Eng'));
    }
}
```

- [ ] Run tests:
```bash
cd packages/aero-hrm && ../../vendor/bin/phpunit --filter=DepartmentControllerTest --testdox
```

- [ ] Commit:
```bash
git add packages/aero-hrm/tests/Feature/OrgStructure/DepartmentControllerTest.php
git commit -m "test(aero-hrm): DepartmentController feature tests (CRUD + chart)"
```

---

## Acceptance checklist

- [ ] All four migrations applied successfully.
- [ ] Hierarchical Department model returns recursive tree via `orgChart`.
- [ ] Every route guarded by `hrmac:hrm.org-structure.*`.
- [ ] AuditService called on each create/update/delete.
- [ ] Designations, Grades, WorkLocations expose modal CRUD with pagination.
- [ ] DepartmentControllerTest has 6 passing methods.
- [ ] UI bundle builds without errors.
