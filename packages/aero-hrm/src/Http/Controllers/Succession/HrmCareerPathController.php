<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Succession;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmCareerPath;
use Aero\HRM\Services\Succession\MilestoneProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class HrmCareerPathController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $r): Response
    {
        $filters = $r->only(['q']);

        return Inertia::render('HRM/Succession/CareerPaths/Index', [
            'careerPaths' => HrmCareerPath::query()
                ->withCount('milestones')
                ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%$v%"))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Succession/CareerPaths/Create', [
            'roles' => Designation::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_role_id' => ['nullable', 'integer', 'exists:designations,id'],
            'milestones' => ['nullable', 'array'],
            'milestones.*.name' => ['required', 'string', 'max:255'],
            'milestones.*.requirements' => ['nullable', 'array'],
        ]);

        $path = DB::transaction(function () use ($data) {
            $path = HrmCareerPath::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::random(6),
                'description' => $data['description'] ?? null,
                'target_role_id' => $data['target_role_id'] ?? null,
                'is_active' => true,
            ]);

            foreach ($data['milestones'] ?? [] as $index => $row) {
                $path->milestones()->create([
                    'name' => $row['name'],
                    'order_index' => $index,
                    'requirements' => $row['requirements'] ?? null,
                ]);
            }

            $this->audit->log(
                event: 'CAREER_PATH_CREATED',
                action: 'create',
                subject: $path,
                description: "Created career path: {$path->name}"
            );

            return $path;
        });

        return redirect()->route('hrm.career-paths.show', $path->slug);
    }

    public function show(HrmCareerPath $careerPath, MilestoneProgressService $progress): Response
    {
        $careerPath->load(['milestones', 'employees']);

        $employeeProgress = $careerPath->employees->map(function (Employee $e) use ($careerPath, $progress) {
            return [
                'employee_id' => $e->id,
                'name' => $e->first_name.' '.$e->last_name,
                'milestones' => $progress->progressFor($careerPath, $e),
            ];
        });

        return Inertia::render('HRM/Succession/CareerPaths/Show', [
            'careerPath' => $careerPath,
            'employeeProgress' => $employeeProgress,
        ]);
    }

    public function assign(Request $r, HrmCareerPath $careerPath): RedirectResponse
    {
        $data = $r->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);

        DB::transaction(function () use ($data, $careerPath) {
            $careerPath->employees()->syncWithoutDetaching([
                $data['employee_id'] => ['assigned_at' => now()],
            ]);

            $this->audit->log(
                event: 'CAREER_PATH_EMPLOYEE_ASSIGNED',
                action: 'assign',
                subject: $careerPath,
                description: "Assigned employee ID {$data['employee_id']} to career path: {$careerPath->name}"
            );
        });

        return back();
    }
}
