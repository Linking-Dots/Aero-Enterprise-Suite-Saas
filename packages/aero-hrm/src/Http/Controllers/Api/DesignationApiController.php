<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Api;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Designation;
use Illuminate\Http\JsonResponse;

/**
 * Audit D24 — Designation (job-title) lookup API.
 *
 * Read-only list for mobile signup flows + integration form-builders.
 * No pagination — small dropdown dataset.
 */
class DesignationApiController extends Controller
{
    /**
     * GET /api/hrm/designations
     */
    public function index(): JsonResponse
    {
        $this->authorize('hrm.designations.list.view');

        $designations = Designation::query()
            ->orderBy('title')
            ->get(['id', 'title', 'department_id'])
            ->map(fn (Designation $d) => [
                'id'            => $d->id,
                'title'         => $d->title,
                'department_id' => $d->department_id,
            ])
            ->all();

        return response()->json([
            'data' => $designations,
            'meta' => ['total' => count($designations)],
        ]);
    }
}
