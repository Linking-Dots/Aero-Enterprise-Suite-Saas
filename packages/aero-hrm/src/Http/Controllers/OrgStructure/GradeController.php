<?php

declare(strict_types=1);

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

    public function update(GradeRequest $request, Grade|int|string $grade): RedirectResponse
    {
        if (! $grade instanceof Grade) {
            $grade = Grade::findOrFail($grade);
        }
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

    public function destroy(Grade|int|string $grade): RedirectResponse
    {
        $this->authorize('hrm.org-structure.grades.edit');
        if (! $grade instanceof Grade) {
            $grade = Grade::findOrFail($grade);
        }
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
