<?php

declare(strict_types=1);

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
            'types' => ['office', 'remote', 'hybrid', 'site'],
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

    public function update(WorkLocationRequest $request, WorkLocation|int|string $workLocation): RedirectResponse
    {
        if (! $workLocation instanceof WorkLocation) {
            $workLocation = WorkLocation::findOrFail($workLocation);
        }
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

    public function destroy(WorkLocation|int|string $workLocation): RedirectResponse
    {
        $this->authorize('hrm.org-structure.work-locations.edit');
        if (! $workLocation instanceof WorkLocation) {
            $workLocation = WorkLocation::findOrFail($workLocation);
        }
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
