<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Succession;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmTalentPoolMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmTalentPoolController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $r): Response
    {
        return Inertia::render('HRM/Succession/TalentPool/Index', [
            'members' => HrmTalentPoolMember::with('employee:id,first_name,last_name')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'employees' => Employee::select('id', 'first_name', 'last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function add(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'tier' => ['required', 'string', 'in:high_potential,key_talent,emerging'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $r) {
            $member = HrmTalentPoolMember::updateOrCreate(
                ['employee_id' => $data['employee_id']],
                [
                    'tier' => $data['tier'],
                    'notes' => $data['notes'] ?? null,
                    'added_by' => $r->user()->id,
                ]
            );

            $this->audit->log(
                event: 'TALENT_POOL_MEMBER_ADDED',
                action: 'create',
                subject: $member,
                description: "Added employee ID {$data['employee_id']} to talent pool with tier: {$data['tier']}"
            );
        });

        return back();
    }

    public function remove(HrmTalentPoolMember $member): RedirectResponse
    {
        DB::transaction(function () use ($member) {
            $this->audit->log(
                event: 'TALENT_POOL_MEMBER_REMOVED',
                action: 'delete',
                subject: $member,
                description: "Removed employee ID {$member->employee_id} from talent pool"
            );

            $member->delete();
        });

        return back();
    }
}
