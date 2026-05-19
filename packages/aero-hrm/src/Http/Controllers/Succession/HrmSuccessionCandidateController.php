<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Succession;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmSuccessionCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class HrmSuccessionCandidateController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $r): Response
    {
        $filters = $r->only(['role_id', 'readiness']);

        return Inertia::render('HRM/Succession/Candidates/Index', [
            'candidates' => HrmSuccessionCandidate::with([
                'role:id,name',
                'employee:id,first_name,last_name',
            ])
                ->when($filters['role_id'] ?? null, fn ($q, $v) => $q->where('role_id', $v))
                ->when($filters['readiness'] ?? null, fn ($q, $v) => $q->where('readiness', $v))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'roles' => Designation::select('id', 'name')->get(),
            'employees' => Employee::select('id', 'first_name', 'last_name')->orderBy('first_name')->get(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'role_id' => ['required', 'integer', 'exists:designations,id'],
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
                Rule::unique('hrm_succession_candidates')->where(fn ($q) => $q->where('role_id', $r->input('role_id'))),
            ],
            'readiness' => ['required', 'string', 'in:ready_now,1_2_years,3_5_years'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $r) {
            $candidate = HrmSuccessionCandidate::create([
                'role_id' => $data['role_id'],
                'employee_id' => $data['employee_id'],
                'readiness' => $data['readiness'],
                'notes' => $data['notes'] ?? null,
                'nominated_by' => $r->user()->id,
            ]);

            $this->audit->log(
                event: 'SUCCESSION_CANDIDATE_NOMINATED',
                action: 'create',
                subject: $candidate,
                description: "Nominated employee ID {$data['employee_id']} for role ID {$data['role_id']} with readiness: {$data['readiness']}"
            );
        });

        return back();
    }

    public function destroy(HrmSuccessionCandidate $candidate): RedirectResponse
    {
        DB::transaction(function () use ($candidate) {
            $this->audit->log(
                event: 'SUCCESSION_CANDIDATE_REMOVED',
                action: 'delete',
                subject: $candidate,
                description: "Removed succession candidate employee ID {$candidate->employee_id} for role ID {$candidate->role_id}"
            );

            $candidate->delete();
        });

        return back();
    }
}
