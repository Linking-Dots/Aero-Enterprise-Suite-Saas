<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Payroll;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

/**
 * Creates a full PayrollRun for a list of employees.
 *
 * Responsibilities:
 *  1. Create the PayrollRun header record.
 *  2. For each employee with an active salary structure, compute pay via PayrollCalculator.
 *  3. Persist a Payslip per employee including encrypted bank PII.
 *  4. Update the run totals and audit-log the event.
 */
final class PayrollRunGenerator
{
    public function __construct(
        private readonly PayrollCalculator $calculator,
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * @param  array{label: string, period_start: string, period_end: string}  $payload
     * @param  list<int>  $employeeIds
     */
    public function create(array $payload, array $employeeIds): PayrollRun
    {
        return DB::transaction(function () use ($payload, $employeeIds): PayrollRun {
            $run = PayrollRun::create([
                'label'        => $payload['label'],
                'period_start' => $payload['period_start'],
                'period_end'   => $payload['period_end'],
                'status'       => PayrollRun::STATUS_DRAFT,
            ]);

            $year       = (int) date('Y', strtotime($payload['period_end']));
            $totalGross = 0.0;
            $totalNet   = 0.0;

            $employees = Employee::with(['salaryStructure', 'bankDetail'])
                ->whereIn('id', $employeeIds)
                ->get();

            foreach ($employees as $employee) {
                // Use the first active salary structure on the employee.
                $struct = $employee->salaryStructure
                    ->where('is_active', true)
                    ->first();

                if ($struct === null) {
                    // Skip employees without an active structure.
                    continue;
                }

                // Resolve the SalaryStructure (v2) linked by the EmployeeSalaryStructure junction.
                // The salary_component_id on the junction is used as the structure id in v2 context.
                // Prefer a direct salary_structure_id if it exists, otherwise use component_id.
                $salaryStructureId = $struct->salary_structure_id ?? $struct->salary_component_id ?? null;

                if ($salaryStructureId === null) {
                    continue;
                }

                $salaryStructure = \Aero\HRM\Models\SalaryStructure::find($salaryStructureId);

                if ($salaryStructure === null || ! $salaryStructure->active) {
                    continue;
                }

                $breakdown = $this->calculator->compute($salaryStructure, $year);

                $bankDetail = $employee->bankDetail;

                Payslip::create([
                    'payroll_run_id'      => $run->id,
                    'employee_id'         => $employee->id,
                    'gross'               => $breakdown['gross'],
                    'tax'                 => $breakdown['tax'],
                    'deductions_total'    => $breakdown['deductions'],
                    'net'                 => $breakdown['net'],
                    'line_items'          => $breakdown['lines'],
                    'employee_snapshot'   => $this->buildSnapshot($employee),
                    'bank_account_number' => $bankDetail?->account_number,
                    'bank_name'           => $bankDetail?->bank_name,
                    'bank_routing_number' => $bankDetail?->routing_number,
                ]);

                $totalGross += $breakdown['gross'];
                $totalNet   += $breakdown['net'];
            }

            // Update run totals (allowed — run is not yet locked).
            $run->total_gross = round($totalGross, 2);
            $run->total_net   = round($totalNet, 2);
            $run->save();

            $this->audit->log(
                event: AuditEventType::PAYROLL_RUN->value,
                action: 'run_created',
                subject: $run,
                description: "Payroll run '{$run->label}' created with ".count($employeeIds).' employee(s).',
                after: [
                    'period_start' => $run->period_start->toDateString(),
                    'period_end'   => $run->period_end->toDateString(),
                    'total_gross'  => $run->total_gross,
                    'total_net'    => $run->total_net,
                ],
            );

            return $run;
        });
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildSnapshot(Employee $employee): array
    {
        return [
            'id'              => $employee->id,
            'employee_code'   => $employee->employee_code,
            'name'            => $employee->user?->name,
            'department'      => $employee->department?->name,
            'designation'     => $employee->designation?->title,
            'employment_type' => $employee->employment_type,
        ];
    }
}
