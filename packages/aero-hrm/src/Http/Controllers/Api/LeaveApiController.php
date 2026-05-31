<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Api;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\LeaveApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HRM Push H.T2 — Leave REST API.
 *
 * Exposes leave application read/submit for mobile self-service apps.
 * Approval flows stay on the Inertia surface — too complex for first-cut API.
 */
class LeaveApiController extends Controller
{
    /**
     * GET /api/hrm/leave-applications
     *
     * Lists the authenticated user's own leave applications by default.
     * Admins (with hrm.leaves.applications.list.view) can pass ?user_id=
     * to query another user's applications.
     */
    public function index(Request $request): JsonResponse
    {
        $currentUserId = (int) $request->user()->id;
        $targetUserId = (int) $request->input('user_id', $currentUserId);

        if ($targetUserId !== $currentUserId) {
            $this->authorize('hrm.leaves.applications.list.view');
        }

        $applications = LeaveApplication::query()
            ->where('user_id', $targetUserId)
            ->with(['leaveType:id,name,code', 'approver:id,name'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('start_date')
            ->paginate($this->boundedPerPage($request, 20, 100))
            ->withQueryString();

        return response()->json([
            'data' => array_map(fn ($a) => $this->transform($a), $applications->items()),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page'    => $applications->lastPage(),
                'total'        => $applications->total(),
            ],
        ]);
    }

    /**
     * GET /api/hrm/leave-applications/{leaveApplication}
     */
    public function show(LeaveApplication $leaveApplication, Request $request): JsonResponse
    {
        // Owner can always view own application; non-owners need permission
        if ($leaveApplication->user_id !== $request->user()->id) {
            $this->authorize('hrm.leaves.applications.list.view');
        }

        $leaveApplication->load(['leaveType:id,name,code', 'approver:id,name', 'user:id,name']);

        return response()->json([
            'data' => $this->transform($leaveApplication),
        ]);
    }

    /**
     * POST /api/hrm/leave-applications
     *
     * Submit a new leave request. Validates the same way as the Inertia
     * form. Returns the created record + 201.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['required', 'string', 'max:1000'],
            'half_day'      => ['nullable', 'boolean'],
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status']  = 'pending';

        $application = LeaveApplication::create($data);
        $application->load(['leaveType:id,name,code']);

        return response()->json([
            'data' => $this->transform($application),
        ], 201);
    }

    protected function transform(LeaveApplication $a): array
    {
        return [
            'id'              => $a->id,
            'user_id'         => $a->user_id,
            'user_name'       => $a->user?->name,
            'leave_type'      => $a->leaveType?->name,
            'leave_type_code' => $a->leaveType?->code,
            'start_date'      => optional($a->start_date)->toDateString(),
            'end_date'        => optional($a->end_date)->toDateString(),
            'days'            => $a->days,
            'reason'          => $a->reason,
            'status'          => $a->status,
            'approver'        => $a->approver?->name,
            'created_at'      => optional($a->created_at)->toIso8601String(),
        ];
    }
}
