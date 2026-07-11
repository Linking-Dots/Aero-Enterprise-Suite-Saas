<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Leave;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Concerns\ProvidesLeaveRailStats;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Leave\LeaveTypeRequest;
use Aero\HRM\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    use ProvidesLeaveRailStats;

    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(): Response
    {
        $this->authorize('hrm.leaves.leave-types.view');

        return Inertia::render('HRM/Leave/Types/Index', [
            'types' => LeaveType::query()->orderBy('name')->paginate(20),
            'stats' => $this->leaveRailStats() + [
                'types_total'  => LeaveType::count(),
                'types_active' => LeaveType::where('is_active', true)->count(),
                'types_paid'   => LeaveType::where('is_paid', true)->count(),
            ],
        ]);
    }

    public function store(LeaveTypeRequest $request): RedirectResponse
    {
        $type = LeaveType::create($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_CREATED->value,
            action: 'created',
            subject: $type,
            description: "Leave type {$type->name} created",
        );

        return back()->with('success', 'Leave type created.');
    }

    public function update(LeaveTypeRequest $request, string $type): RedirectResponse
    {
        // Resolve the model by the NAMED route param: under tenant-subdomain
        // routing a scalar/model method arg binds to the FIRST route segment
        // ({tenant}) instead of {type}, so read it by name explicitly. This is
        // the app-wide tenant route-binding trap.
        $type = LeaveType::findOrFail($request->route('type'));
        $before = $type->only(['name', 'code', 'days_per_year', 'is_active']);
        $type->update($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_UPDATED->value,
            action: 'updated',
            subject: $type,
            description: "Leave type {$type->name} updated",
            before: $before,
            after: $type->only(['name', 'code', 'days_per_year', 'is_active']),
        );

        return back()->with('success', 'Leave type updated.');
    }

    public function destroy(\Illuminate\Http\Request $request, string $type): RedirectResponse
    {
        // Resolve by named route param — see update() re: the tenant route-binding trap.
        $type = LeaveType::findOrFail($request->route('type'));
        $this->authorize('hrm.leaves.leave-types.edit');
        $type->delete();

        $this->audit->log(
            event: AuditEventType::RECORD_DELETED->value,
            action: 'deleted',
            subject: $type,
            description: "Leave type {$type->name} deleted",
        );

        return back()->with('success', 'Leave type deleted.');
    }
}
