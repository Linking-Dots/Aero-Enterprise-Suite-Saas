<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Succession;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\HrmTalentMobilityPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmTalentMobilityController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $r): Response
    {
        $filters = $r->only(['status', 'type']);

        return Inertia::render('HRM/Succession/Mobility/Index', [
            'postings' => HrmTalentMobilityPosting::with([
                'department:id,name',
                'role:id,name',
                'creator:id,name',
            ])
                ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'departments' => Department::select('id', 'name')->get(),
            'roles' => Designation::select('id', 'name')->get(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:transfer,project,secondment,promotion'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'role_id' => ['nullable', 'integer', 'exists:designations,id'],
            'closes_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($data, $r) {
            $posting = HrmTalentMobilityPosting::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'department_id' => $data['department_id'] ?? null,
                'role_id' => $data['role_id'] ?? null,
                'closes_at' => $data['closes_at'] ?? null,
                'status' => HrmTalentMobilityPosting::STATUS_OPEN,
                'created_by' => $r->user()->id,
            ]);

            $this->audit->log(
                event: 'TALENT_MOBILITY_POSTING_CREATED',
                action: 'create',
                subject: $posting,
                description: "Created talent mobility posting: {$posting->title}"
            );
        });

        return back();
    }
}
