<?php

declare(strict_types=1);

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
            'grades' => Grade::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only('search'),
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

    public function update(DesignationRequest $request, Designation|int|string $designation): RedirectResponse
    {
        if (! $designation instanceof Designation) {
            $designation = Designation::findOrFail($designation);
        }
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

    public function destroy(Designation|int|string $designation): RedirectResponse
    {
        $this->authorize('hrm.org-structure.designations.edit');
        if (! $designation instanceof Designation) {
            $designation = Designation::findOrFail($designation);
        }
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
