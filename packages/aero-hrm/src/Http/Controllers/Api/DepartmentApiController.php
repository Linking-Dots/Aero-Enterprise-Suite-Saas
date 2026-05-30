<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Api;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Department;
use Illuminate\Http\JsonResponse;

/**
 * Audit D24 — Department lookup API.
 *
 * Read-only list for mobile signup flows + integration form-builders
 * that need the tenant's department metadata. No pagination — this is
 * a small dropdown-list dataset.
 */
class DepartmentApiController extends Controller
{
    /**
     * GET /api/hrm/departments
     */
    public function index(): JsonResponse
    {
        $this->authorize('hrm.departments.list.view');

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'parent_id'])
            ->map(fn (Department $d) => [
                'id'        => $d->id,
                'name'      => $d->name,
                'code'      => $d->code,
                'parent_id' => $d->parent_id,
            ])
            ->all();

        return response()->json([
            'data' => $departments,
            'meta' => ['total' => count($departments)],
        ]);
    }
}
