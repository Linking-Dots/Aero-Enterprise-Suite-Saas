<?php

namespace Aero\HRM\Http\Controllers\Disciplinary;

use Aero\HRM\Models\DisciplinaryActionType;
use Aero\HRM\Models\DisciplinaryCase;
use Aero\HRM\Models\Employee;
use Aero\HRM\Http\Requests\StoreDisciplinaryCaseRequest;
use Aero\HRM\Services\DisciplinaryWorkflowService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisciplinaryCaseController extends Controller
{
    public function __construct(private DisciplinaryWorkflowService $disciplinaryService) {}
    public function index()
    {
        return Inertia::render('HRM/Disciplinary/DisciplinaryCasesIndex', [
            'title' => 'Disciplinary Cases',
            'actionTypes' => DisciplinaryActionType::active()->get(),
            'employees' => Employee::with('user:id,name,email')
                ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'name' => $e->user?->name ?? $e->first_name.' '.$e->last_name,
                    'email' => $e->user?->email,
                    'employee_code' => $e->employee_code,
                ]),
        ]);
    }

    public function paginate(Request $request)
    {
        $perPage = $request->get('perPage', 30);
        $query = DisciplinaryCase::with(['employee', 'actionType', 'reporter'])
            ->orderBy('created_at', 'desc');

        if ($search = $request->get('search')) {
            $query->where('case_number', 'like', "%{$search}%")
                ->orWhereHas('employee', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($employeeId = $request->get('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        return response()->json($query->paginate($perPage));
    }

    public function stats()
    {
        return response()->json([
            'total' => DisciplinaryCase::count(),
            'pending' => DisciplinaryCase::pending()->count(),
            'investigating' => DisciplinaryCase::investigating()->count(),
            'closed' => DisciplinaryCase::closed()->count(),
        ]);
    }

    public function store(StoreDisciplinaryCaseRequest $request)
    {
        $validated = $request->validated();

        $validated['reported_by'] = $request->user()->id;
        $case = $this->disciplinaryService->openCase($validated);

        return response()->json(['message' => 'Disciplinary case created', 'case' => $case], 201);
    }

    public function startInvestigation(Request $request, int $id)
    {
        $case = DisciplinaryCase::findOrFail($id);

        $validated = $request->validate([
            'investigation_notes' => 'nullable|string',
        ]);

        try {
            $case = $this->disciplinaryService->startInvestigation(
                $case,
                $request->user()->id,
                $validated['investigation_notes'] ?? null
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Investigation started']);
    }

    public function takeAction(Request $request, int $id)
    {
        $validated = $request->validate([
            'action_taken' => 'required|string',
            'action_date' => 'required|date',
        ]);

        $case = DisciplinaryCase::findOrFail($id);

        $case->update(array_merge($validated, [
            'status' => DisciplinaryCase::STATUS_ACTION_TAKEN,
            'action_by' => $request->user()->id,
        ]));

        return response()->json(['message' => 'Action recorded successfully']);
    }

    public function close(Request $request, int $id)
    {
        $case = DisciplinaryCase::findOrFail($id);

        if (! $case->canBeClosed()) {
            return response()->json(['message' => 'Case cannot be closed'], 422);
        }

        $validated = $request->validate([
            'closed_notes' => 'nullable|string',
        ]);

        $case->update([
            'status' => DisciplinaryCase::STATUS_CLOSED,
            'closed_date' => now(),
        ]);

        return response()->json(['message' => 'Case closed successfully']);
    }

    public function appeal(Request $request, int $id)
    {
        $case = DisciplinaryCase::findOrFail($id);

        if (! $case->canBeAppealed()) {
            return response()->json(['message' => 'Case cannot be appealed'], 422);
        }

        $validated = $request->validate([
            'appeal_notes' => 'required|string',
        ]);

        $case->update(array_merge($validated, [
            'appeal_filed' => true,
        ]));

        return response()->json(['message' => 'Appeal filed successfully']);
    }

    public function update(Request $request, int $id)
    {
        $case = DisciplinaryCase::findOrFail($id);

        $validated = $request->validate([
            'incident_description' => 'required|string',
            'investigation_notes' => 'nullable|string',
            'employee_statement' => 'nullable|string',
            'witness_statements' => 'nullable|string',
        ]);

        $case->update($validated);

        return response()->json(['message' => 'Case updated', 'case' => $case]);
    }

    public function destroy(int $id)
    {
        $case = DisciplinaryCase::findOrFail($id);

        if ($case->status !== DisciplinaryCase::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending cases can be deleted'], 422);
        }

        $case->delete();

        return response()->json(['message' => 'Case deleted']);
    }
}
