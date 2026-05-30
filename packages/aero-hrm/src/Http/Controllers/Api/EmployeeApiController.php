<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Api;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HRM Push H.T2 — Employee REST API.
 *
 * Phase 1 audit found aero-hrm shipped Inertia controllers only —
 * no `routes/api.php`, no JSON endpoints. External consumers (mobile
 * apps, BI tools, partner integrations) had no way to read HR data.
 *
 * This controller is the JSON twin of Employee/EmployeeController.
 * It SHARES the same HRMAC permissions (hrm.employees.list.view,
 * hrm.employees.list.show) AND the same EmployeeCrudService for
 * mutations — there's no "API permission" loophole.
 *
 * Authentication: Sanctum/PAT (Personal Access Token). Operator
 * issues tokens via /admin/api-keys (aero-core ApiKey surface).
 *
 * Pagination: bounded via boundedPerPage() helper from the base
 * Controller (Phase 0 T10) — defaults to 20, capped at 100.
 */
class EmployeeApiController extends Controller
{
    /**
     * GET /api/hrm/employees
     *
     * Lists employees, paginated, filterable by department/status/
     * employment_type/search.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('hrm.employees.list.view');

        $filters = $request->only(['search', 'department_id', 'status', 'employment_type']);

        $employees = Employee::query()
            ->with(['user:id,name,email', 'department:id,name', 'designation:id,title'])
            ->filter($filters)
            ->orderByDesc('id')
            ->paginate($this->boundedPerPage($request, 20, 100))
            ->withQueryString();

        return response()->json([
            'data' => $employees->items() ? array_map(fn ($e) => $this->transform($e), $employees->items()) : [],
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
                'per_page'     => $employees->perPage(),
                'total'        => $employees->total(),
            ],
            'links' => [
                'first' => $employees->url(1),
                'last'  => $employees->url($employees->lastPage()),
                'prev'  => $employees->previousPageUrl(),
                'next'  => $employees->nextPageUrl(),
            ],
        ]);
    }

    /**
     * GET /api/hrm/employees/{employee}
     */
    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('hrm.employees.list.show');

        $employee->load(['user:id,name,email', 'department:id,name', 'designation:id,title']);

        return response()->json([
            'data' => $this->transform($employee),
        ]);
    }

    /**
     * Internal transformer — same shape as the Inertia controller's
     * `through()` callback so frontend and API consumers see identical
     * employee records.
     */
    protected function transform(Employee $e): array
    {
        return [
            'id'              => $e->id,
            'employee_code'   => $e->employee_code,
            'name'            => $e->user?->name,
            'email'           => $e->user?->email,
            'department'      => $e->department?->name,
            'department_id'   => $e->department_id,
            'designation'     => $e->designation?->title,
            'designation_id'  => $e->designation_id,
            'employment_type' => $e->employment_type,
            'status'          => $e->status,
            'date_of_joining' => optional($e->date_of_joining)->toDateString(),
        ];
    }
}
