<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Payroll;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Concerns\ProvidesPayrollRailStats;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\PayComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PayComponentController extends Controller
{
    use ProvidesPayrollRailStats;

    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(): Response
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-components.view');

        $components = PayComponent::query()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (PayComponent $c) => [
                'id'        => $c->id,
                'code'      => $c->code,
                'name'      => $c->name,
                'kind'      => $c->kind,
                'calc_type' => $c->calc_type,
                'value'     => (float) $c->value,
                'taxable'   => $c->taxable,
                'active'    => $c->active,
            ]);

        return Inertia::render('HRM/Payroll/Components/Index', [
            'components' => $components,
            'stats' => $this->payrollRailStats() + [
                'components_total'     => PayComponent::count(),
                'components_earning'   => PayComponent::where('kind', 'earning')->count(),
                'components_deduction' => PayComponent::where('kind', 'deduction')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-components.create');

        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50', Rule::unique('hrm_pay_components', 'code')],
            'name'      => ['required', 'string', 'max:100'],
            'kind'      => ['required', Rule::in(['earning', 'deduction'])],
            'calc_type' => ['required', Rule::in(['fixed', 'percent_of_basic', 'percent_of_gross', 'formula'])],
            'value'     => ['required', 'numeric', 'min:0'],
            'taxable'   => ['boolean'],
            'active'    => ['boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            $component = PayComponent::create($data);

            $this->audit->log(
                event: AuditEventType::RECORD_CREATED->value,
                action: 'created',
                subject: $component,
                description: "Pay component '{$component->code}' created.",
            );
        });

        return back();
    }

    public function update(Request $request, PayComponent $component): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-components.update');

        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50', Rule::unique('hrm_pay_components', 'code')->ignore($component->id)],
            'name'      => ['required', 'string', 'max:100'],
            'kind'      => ['required', Rule::in(['earning', 'deduction'])],
            'calc_type' => ['required', Rule::in(['fixed', 'percent_of_basic', 'percent_of_gross', 'formula'])],
            'value'     => ['required', 'numeric', 'min:0'],
            'taxable'   => ['boolean'],
            'active'    => ['boolean'],
        ]);

        DB::transaction(function () use ($component, $data): void {
            $before = $component->only(['code', 'name', 'kind', 'calc_type', 'value', 'taxable', 'active']);
            $component->fill($data)->save();

            $this->audit->log(
                event: AuditEventType::RECORD_UPDATED->value,
                action: 'updated',
                subject: $component,
                description: "Pay component '{$component->code}' updated.",
                before: $before,
                after: $component->only(['code', 'name', 'kind', 'calc_type', 'value', 'taxable', 'active']),
            );
        });

        return back();
    }

    public function destroy(PayComponent $component): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.payroll.salary-components.delete');

        DB::transaction(function () use ($component): void {
            $this->audit->log(
                event: AuditEventType::RECORD_DELETED->value,
                action: 'deleted',
                subject: $component,
                description: "Pay component '{$component->code}' deleted.",
            );

            $component->delete();
        });

        return back();
    }
}
