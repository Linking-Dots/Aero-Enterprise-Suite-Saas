---
name: backend-engine
description: "Build Laravel backend code in packages/aero-*. Enforces Form Requests over inline validation, config() over env(), thin controllers, HRMAC middleware, and Eloquent best practices. Detects existing violations across the codebase."
---

# AEOS365 Backend Engineering Skill

## Monorepo Boundaries

- **ALL code goes in `packages/aero-*/src/`** — never modify `aeos365/app/`, `aeos365/resources/`, `aeos365/routes/`.
- Host app `aeos365` is a dumb wrapper: `.env`, `composer.json`, `vite.config.js`, `bootstrap/`, `public/`, `storage/` only.
- Each package has its own `composer.json` with `extra.laravel.providers` and `extra.aero` metadata.

## Service Provider Naming

- **MUST be `Aero{Module}ServiceProvider`** (e.g., `AeroHrmServiceProvider`).
- Fix legacy names (`CmsServiceProvider`, `BlockchainServiceProvider`) when editing.
- Each provider extends `Illuminate\Support\ServiceProvider` or `Aero\Core\Providers\AbstractModuleProvider`.

## Form Requests (MANDATORY)

**NEVER use inline validation in controllers.**

### Wrong:
```php
public function store(Request $request) {
    $request->validate(['name' => 'required|string|max:255']);
}
```

### Right:
```php
// packages/aero-hrm/src/Http/Requests/StoreEmployeeRequest.php
namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('hrm.employees.create');
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'department' => ['nullable', 'exists:departments,id'],
            'hire_date'  => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Employee name is required.',
            'email.unique'    => 'This email is already registered.',
        ];
    }
}
```

```php
// Controller
public function store(StoreEmployeeRequest $request)
{
    $employee = $this->employeeService->create($request->validated());
    return response()->json(['data' => $employee, 'message' => 'Employee created.']);
}
```

## Thin Controllers

- **Maximum 30 lines per controller action.**
- Delegate ALL business logic to Service or Action classes.
- Controllers handle: request injection, validation (via Form Request), service call, response shaping.

### Controller Pattern:
```php
namespace Aero\HRM\Http\Controllers\Employee;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $service,
    ) {}

    public function index(): \Inertia\Response
    {
        return Inertia::render('HRM/EmployeeList', [
            'title'        => 'Employee Management',
            'departments'  => Department::where('is_active', true)->get(),
            'designations' => Designation::where('is_active', true)->get(),
        ]);
    }

    public function list(): JsonResponse
    {
        $employees = $this->service->paginate(request()->only(['search', 'department', 'status']));
        return response()->json($employees);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->service->create($request->validated());
        return response()->json(['data' => $employee, 'message' => 'Employee created successfully.']);
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->json(['data' => $employee->load(['user', 'department', 'designation'])]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee = $this->service->update($employee, $request->validated());
        return response()->json(['data' => $employee, 'message' => 'Employee updated successfully.']);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->service->delete($employee);
        return response()->json(['message' => 'Employee deleted successfully.']);
    }
}
```

## Response Shapes

### Inertia Page Response:
```php
return Inertia::render('HRM/EmployeeList', [
    'title'        => 'Page Title',
    'items'        => $items,
    'filters'      => $request->only(['search', 'status', 'per_page']),
    'meta'         => ['total' => $total, 'canCreate' => Gate::allows('hrm.employees.create')],
]);
```

### Paginated API Response:
```php
return response()->json([
    'items'       => $paginator->items(),
    'total'       => $paginator->total(),
    'currentPage' => $paginator->currentPage(),
    'lastPage'    => $paginator->lastPage(),
    'perPage'     => $paginator->perPage(),
]);
```

### Single Resource Response:
```php
return response()->json([
    'data'    => $resource,
    'message' => 'Operation successful',
]);
```

## Eloquent Patterns

### N+1 Prevention:
```php
// ALWAYS eager-load
Employee::with(['user', 'department', 'designation', 'manager'])->get();
Employee::withCount('subordinates')->get();

// For lists with counts
Employee::with(['user:id,name'])
    ->withCount(['attendanceRecords', 'leaveRequests'])
    ->orderBy('id')
    ->paginate(30);
```

### Prefer Eloquent over DB facade:
```php
// Good
Employee::query()->where('is_active', true)->get();

// Bad
DB::table('employees')->where('is_active', true)->get();
```

### Route model binding with scoped queries:
```php
// Implicit binding automatically scopes to tenant database
public function show(Employee $employee)
{
    return $employee->load(['user', 'department']);
}
```

## Configuration (NEVER env() outside config/)

### Wrong:
```php
$domain = env('PLATFORM_DOMAIN', 'localhost');
```

### Right:
```php
// In a controller/service:
$domain = config('aero.platform_domain', 'localhost');

// In a config file (packages/aero-core/config/aero.php):
return [
    'platform_domain' => env('PLATFORM_DOMAIN', 'localhost'),
];
```

**Rule:** `env()` is ONLY allowed inside `config/` files. `config()` with a default fallback is required everywhere else.

## Route Middleware Stack

Canonical order for tenant routes:
```php
Route::middleware(['web', 'auth', 'verified', 'hrmac:hrm.employees.view'])
    ->get('/employees', [EmployeeController::class, 'index'])
    ->name('employees.index');
```

- `web` — Laravel web middleware group
- `auth` — authentication check
- `verified` — email verification (if required)
- `hrmac:{module}.{submodule}.{component}.{action}` — HRMAC access control

## Service Class Pattern

```php
namespace Aero\HRM\Services;

use Aero\HRM\Models\Employee;

class EmployeeService
{
    public function __construct(
        protected NotificationService $notifications,
    ) {}

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::create($data);
            $this->notifications->sendWelcome($employee);
            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh();
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    public function paginate(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Employee::query()
            ->with(['user:id,name', 'department'])
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")))
            ->when($filters['department'] ?? null, fn ($q, $dept) => $q->where('department_id', $dept))
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 30);
    }
}
```

## Gap Detection

When editing or auditing backend code, flag these violations:

| Violation | Severity | Fix |
|-----------|----------|-----|
| `$request->validate(` in controller | **CRITICAL** | Extract to Form Request class |
| `env(` outside `config/` files | **CRITICAL** | Replace with `config()` + add to config file |
| Controller action >30 lines | **HIGH** | Extract to Service/Action class |
| `DB::table()` when Eloquent could be used | **MEDIUM** | Use `Model::query()` |
| Missing `hrmac:` middleware on route | **HIGH** | Add `hrmac:{module}.{submodule}.{component}.{action}` |
| Missing eager loading (`with()`) | **MEDIUM** | Add `->with(['relation'])` |
| Cross-package concrete class import in Core | **HIGH** | Use interface/contract instead |

## Reference Files

- Base controller: `packages/aero-hrm/src/Http/Controllers/Controller.php`
- Service provider base: `packages/aero-core/src/Providers/AbstractModuleProvider.php`
- Core service provider: `packages/aero-core/src/AeroCoreServiceProvider.php`
- HRM routes (canonical): `packages/aero-hrm/routes/web.php`
