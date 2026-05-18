<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\SelfServiceAuditEvents;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Services\Payroll\PayslipPdfRenderer;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PayslipController extends SelfServiceController
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly PayslipPdfRenderer $renderer,
    ) {}

    public function index(): InertiaResponse
    {
        $employee = $this->employee();

        $payslips = Payslip::where('employee_id', $employee->id)
            ->orderByDesc('period_end')
            ->paginate(12);

        return Inertia::render('HRM/SelfService/Payslips', [
            'payslips' => $payslips,
        ]);
    }

    public function show(Payslip $payslip): InertiaResponse
    {
        $employee = $this->employee();

        abort_unless((int) $payslip->employee_id === $employee->id, 403, 'Forbidden.');

        $payslip->load('run');

        $this->audit->log(
            event: SelfServiceAuditEvents::PAYSLIP_VIEWED,
            action: 'viewed',
            subject: $payslip,
            description: "Payslip #{$payslip->id} viewed by employee #{$employee->id}",
        );

        return Inertia::render('HRM/SelfService/PayslipShow', [
            'payslip' => $payslip,
        ]);
    }

    public function download(Payslip $payslip): Response
    {
        $employee = $this->employee();

        abort_unless((int) $payslip->employee_id === $employee->id, 403, 'Forbidden.');

        $this->audit->log(
            event: SelfServiceAuditEvents::PAYSLIP_DOWNLOADED,
            action: 'downloaded',
            subject: $payslip,
            description: "Payslip #{$payslip->id} downloaded by employee #{$employee->id}",
        );

        return $this->renderer->stream($payslip);
    }
}
