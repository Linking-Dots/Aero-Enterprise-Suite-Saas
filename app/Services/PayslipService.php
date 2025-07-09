<?php

namespace App\Services;

use App\Models\HRM\Payroll;
use App\Models\HRM\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\PayslipEmail;

class PayslipService
{
    /**
     * Generate payslip for a payroll
     */
    public function generatePayslip(Payroll $payroll, $options = [])
    {
        $payslipData = $this->preparePayslipData($payroll);

        // Generate PDF
        $pdf = Pdf::loadView('payslips.template', $payslipData);
        $pdf->setPaper('A4', 'portrait');

        // Generate filename
        $filename = $this->generateFilename($payroll);

        // Save PDF to storage
        $pdfPath = "payslips/{$payroll->user_id}/{$filename}";
        Storage::put($pdfPath, $pdf->output());

        // Create payslip record
        $payslip = Payslip::updateOrCreate(
            ['payroll_id' => $payroll->id],
            [
                'payroll_id' => $payroll->id,
                'employee_id' => $payroll->user_id,
                'pay_period' => $payroll->pay_period_start->format('M Y'),
                'pdf_path' => $pdfPath,
                'generated_at' => now(),
                'generated_by' => auth()->id() ?? 1,
                'is_sent' => false,
            ]
        );

        // Send email if requested
        if (isset($options['send_email']) && $options['send_email']) {
            $this->sendPayslipEmail($payslip);
        }

        return $payslip;
    }

    /**
     * Generate payslips for multiple payrolls
     */
    public function generateBulkPayslips($payrollIds, $options = [])
    {
        $results = [];

        foreach ($payrollIds as $payrollId) {
            try {
                $payroll = Payroll::with(['employee', 'allowances', 'deductions'])->find($payrollId);

                if (!$payroll) {
                    $results[] = [
                        'payroll_id' => $payrollId,
                        'status' => 'error',
                        'message' => 'Payroll not found'
                    ];
                    continue;
                }

                $payslip = $this->generatePayslip($payroll, $options);

                $results[] = [
                    'payroll_id' => $payrollId,
                    'payslip_id' => $payslip->id,
                    'employee_name' => $payroll->employee->name,
                    'status' => 'success',
                    'pdf_path' => $payslip->pdf_path
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'payroll_id' => $payrollId,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Send payslip via email
     */
    public function sendPayslipEmail(Payslip $payslip)
    {
        $payroll = $payslip->payroll;
        $employee = $payroll->employee;

        // Get PDF content
        $pdfContent = Storage::get($payslip->pdf_path);

        // Send email
        Mail::to($employee->email)->send(new PayslipEmail($payslip, $pdfContent));

        // Update sent status
        $payslip->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);

        return true;
    }

    /**
     * Download payslip PDF
     */
    public function downloadPayslip(Payslip $payslip)
    {
        if (!Storage::exists($payslip->pdf_path)) {
            // Regenerate if not found
            $this->generatePayslip($payslip->payroll);
        }

        return Storage::download($payslip->pdf_path, $this->generateFilename($payslip->payroll));
    }

    /**
     * Prepare data for payslip generation
     */
    protected function preparePayslipData(Payroll $payroll)
    {
        $employee = $payroll->employee;
        $allowances = $payroll->allowances;
        $deductions = $payroll->deductions;

        // Group allowances and deductions
        $allowanceGroups = $allowances->groupBy('allowance_type');
        $deductionGroups = $deductions->groupBy('deduction_type');

        // Calculate totals
        $totalAllowances = $allowances->sum('amount');
        $totalDeductions = $deductions->sum('amount');

        // Prepare earnings breakdown
        $earnings = [
            [
                'description' => 'Basic Salary',
                'amount' => $payroll->basic_salary,
                'type' => 'basic'
            ]
        ];

        foreach ($allowanceGroups as $type => $groupAllowances) {
            $earnings[] = [
                'description' => $this->formatAllowanceType($type),
                'amount' => $groupAllowances->sum('amount'),
                'type' => 'allowance'
            ];
        }

        if ($payroll->overtime_amount > 0) {
            $earnings[] = [
                'description' => 'Overtime Amount',
                'amount' => $payroll->overtime_amount,
                'type' => 'overtime'
            ];
        }

        // Prepare deductions breakdown
        $deductionsBreakdown = [];
        foreach ($deductionGroups as $type => $groupDeductions) {
            $deductionsBreakdown[] = [
                'description' => $this->formatDeductionType($type),
                'amount' => $groupDeductions->sum('amount'),
                'type' => $type
            ];
        }

        return [
            'company' => [
                'name' => config('app.name', 'Aero-HR'),
                'address' => 'Company Address Line 1',
                'city' => 'City, State - ZIP',
                'phone' => '+1 (555) 123-4567',
                'email' => 'hr@company.com',
            ],
            'employee' => [
                'name' => $employee->name,
                'employee_id' => $employee->employee_id ?? $employee->id,
                'email' => $employee->email,
                'designation' => $employee->designation ?? 'Employee',
                'department' => $employee->department ?? 'General',
                'join_date' => $employee->created_at->format('d-m-Y'),
                'bank_account' => $employee->bank_account ?? 'XXXX-XXXX-XXXX',
                'pan_number' => $employee->pan_number ?? 'XXXXX1234X',
            ],
            'payroll' => [
                'pay_period' => $payroll->pay_period_start->format('M Y'),
                'pay_period_start' => $payroll->pay_period_start->format('d-m-Y'),
                'pay_period_end' => $payroll->pay_period_end->format('d-m-Y'),
                'working_days' => $payroll->working_days,
                'present_days' => $payroll->present_days,
                'absent_days' => $payroll->absent_days,
                'leave_days' => $payroll->leave_days,
                'overtime_hours' => $payroll->overtime_hours,
            ],
            'earnings' => $earnings,
            'deductions' => $deductionsBreakdown,
            'summary' => [
                'basic_salary' => $payroll->basic_salary,
                'gross_salary' => $payroll->gross_salary,
                'total_allowances' => $totalAllowances,
                'total_deductions' => $totalDeductions,
                'net_salary' => $payroll->net_salary,
            ],
            'generated_date' => now()->format('d-m-Y H:i:s'),
        ];
    }

    /**
     * Generate filename for payslip
     */
    protected function generateFilename(Payroll $payroll)
    {
        $employee = $payroll->employee;
        $period = $payroll->pay_period_start->format('M_Y');
        $employeeId = $employee->employee_id ?? $employee->id;

        return "payslip_{$employeeId}_{$period}.pdf";
    }

    /**
     * Format allowance type for display
     */
    protected function formatAllowanceType($type)
    {
        $types = [
            'housing_allowance' => 'Housing Allowance',
            'transport_allowance' => 'Transport Allowance',
            'medical_allowance' => 'Medical Allowance',
            'performance_bonus' => 'Performance Bonus',
            'special_allowance' => 'Special Allowance',
        ];

        return $types[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Format deduction type for display
     */
    protected function formatDeductionType($type)
    {
        $types = [
            'income_tax' => 'Income Tax',
            'professional_tax' => 'Professional Tax',
            'pf_deduction' => 'Provident Fund',
            'esi_deduction' => 'Employee State Insurance',
            'loan_deduction' => 'Loan Deduction',
            'advance_deduction' => 'Advance Salary',
        ];

        return $types[$type] ?? ucwords(str_replace('_', ' ', $type));
    }
}
