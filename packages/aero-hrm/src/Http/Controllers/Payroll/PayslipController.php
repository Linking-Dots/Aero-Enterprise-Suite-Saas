<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Payroll;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Services\Payroll\PayslipPdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PayslipController extends Controller
{
    public function __construct(
        private readonly PayslipPdfRenderer $pdf,
        private readonly AuditServiceInterface $audit,
    ) {}

    public function show(Request $request, Payslip $payslip): InertiaResponse
    {
        $user = $request->user();
        $isSelf = $payslip->employee?->user_id === $user->id;

        if ($isSelf) {
            Gate::authorize('hrmac', 'hrm.employee-self-service.my-payslips.view');
        } else {
            Gate::authorize('hrmac', 'hrm.payroll.payslips.view');
        }

        $payslip->load(['run', 'employee.user:id,name']);

        $this->audit->logAccess(
            resourceType: 'payslip',
            resourceId: $payslip->id,
            subjectLabel: $payslip->employee?->user?->name,
            fields: ['gross', 'tax', 'deductions_total', 'net', 'bank_account_number', 'bank_name', 'bank_routing_number'],
        );

        return Inertia::render('HRM/Payroll/Payslips/Show', [
            'payslip' => [
                'id' => $payslip->id,
                'payroll_run_id' => $payslip->payroll_run_id,
                'employee_id' => $payslip->employee_id,
                'employee_name' => $payslip->employee?->user?->name,
                'employee_code' => $payslip->employee?->employee_code,
                'gross' => $payslip->gross,
                'tax' => $payslip->tax,
                'deductions_total' => $payslip->deductions_total,
                'net' => $payslip->net,
                'line_items' => $payslip->line_items,
                'employee_snapshot' => $payslip->employee_snapshot,
                'period_start' => $payslip->run?->period_start?->toDateString(),
                'period_end' => $payslip->run?->period_end?->toDateString(),
                'run_label' => $payslip->run?->label,
            ],
        ]);
    }

    public function download(Request $request, Payslip $payslip): Response
    {
        $user = $request->user();
        $isSelf = $payslip->employee?->user_id === $user->id;

        if ($isSelf) {
            Gate::authorize('hrmac', 'hrm.employee-self-service.my-payslips.download');
        } else {
            Gate::authorize('hrmac', 'hrm.payroll.payslips.download');
        }

        $this->audit->logAccess(
            resourceType: 'payslip',
            resourceId: $payslip->id,
            subjectLabel: $payslip->employee?->user?->name,
            fields: ['bank_account_number', 'bank_name', 'bank_routing_number'],
        );

        return $this->pdf->stream($payslip);
    }
}
