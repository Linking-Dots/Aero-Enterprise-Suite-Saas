<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Payroll;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Concerns\ProvidesPayrollRailStats;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\PayComponent;
use Aero\HRM\Models\SalaryStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SalaryStructureController extends Controller
{
    use ProvidesPayrollRailStats;

    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(): Response
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-structures.view');

        $structures = SalaryStructure::query()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SalaryStructure $s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'basic'      => (float) $s->basic,
                'components' => count($s->component_ids ?? []),
                'active'     => $s->active,
            ]);

        return Inertia::render('HRM/Payroll/Structures/Index', [
            'structures' => $structures,
            'stats' => $this->payrollRailStats() + [
                'structures_total'  => SalaryStructure::count(),
                'structures_active' => SalaryStructure::where('active', true)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-structures.create');

        return Inertia::render('HRM/Payroll/Structures/Create', [
            'components' => PayComponent::where('active', true)
                ->select('id', 'code', 'name', 'kind', 'calc_type', 'value')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-structures.create');

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'basic'           => ['required', 'numeric', 'min:0'],
            'component_ids'   => ['nullable', 'array'],
            'component_ids.*' => ['integer', Rule::exists('hrm_pay_components', 'id')],
            'active'          => ['boolean'],
        ]);

        $structure = DB::transaction(function () use ($data): SalaryStructure {
            $structure = SalaryStructure::create($data);

            $this->audit->log(
                event: AuditEventType::RECORD_CREATED->value,
                action: 'created',
                subject: $structure,
                description: "Salary structure '{$structure->name}' created.",
            );

            return $structure;
        });

        return redirect()->route('hrm.payroll.structures.index');
    }

    public function update(Request $request, SalaryStructure $structure): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-structures.update');

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'basic'           => ['required', 'numeric', 'min:0'],
            'component_ids'   => ['nullable', 'array'],
            'component_ids.*' => ['integer', Rule::exists('hrm_pay_components', 'id')],
            'active'          => ['boolean'],
        ]);

        DB::transaction(function () use ($structure, $data): void {
            $before = $structure->only(['name', 'basic', 'component_ids', 'active']);
            $structure->fill($data)->save();

            $this->audit->log(
                event: AuditEventType::RECORD_UPDATED->value,
                action: 'updated',
                subject: $structure,
                description: "Salary structure '{$structure->name}' updated.",
                before: $before,
                after: $structure->only(['name', 'basic', 'component_ids', 'active']),
            );
        });

        return back();
    }

    public function destroy(SalaryStructure $structure): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-structures.delete');

        DB::transaction(function () use ($structure): void {
            $this->audit->log(
                event: AuditEventType::RECORD_DELETED->value,
                action: 'deleted',
                subject: $structure,
                description: "Salary structure '{$structure->name}' deleted.",
            );

            $structure->delete();
        });

        return back();
    }
}
