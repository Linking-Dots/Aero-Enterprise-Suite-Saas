<?php

namespace Aero\HRM\Http\Controllers\Disciplinary;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Requests\Disciplinary\StoreActionTypeRequest;
use Aero\HRM\Http\Requests\Disciplinary\UpdateActionTypeRequest;
use Aero\HRM\Models\HrmDisciplinaryActionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmActionTypeController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(): Response
    {
        return Inertia::render('HRM/Disciplinary/ActionTypes/Index', [
            'types' => HrmDisciplinaryActionType::orderBy('name')->paginate(20),
        ]);
    }

    public function store(StoreActionTypeRequest $r): RedirectResponse
    {
        $type = HrmDisciplinaryActionType::create($r->validated());
        $this->audit->log(event: 'ACTION_TYPE_CREATED', action: 'create', subject: $type, description: "Created action type: {$type->name}");

        return back()->with('success', 'Action type created.');
    }

    public function update(UpdateActionTypeRequest $r, HrmDisciplinaryActionType $type): RedirectResponse
    {
        $type->update($r->validated());
        $this->audit->log(event: 'ACTION_TYPE_UPDATED', action: 'update', subject: $type, description: "Updated action type: {$type->name}");

        return back()->with('success', 'Action type updated.');
    }

    public function destroy(HrmDisciplinaryActionType $type): RedirectResponse
    {
        abort_if($type->cases()->exists() || $type->warnings()->exists(), 422, 'Type in use — deactivate instead.');
        $this->audit->log(event: 'ACTION_TYPE_DELETED', action: 'delete', subject: $type, description: "Deleted action type: {$type->name}");
        $type->delete();

        return back()->with('success', 'Action type deleted.');
    }
}
