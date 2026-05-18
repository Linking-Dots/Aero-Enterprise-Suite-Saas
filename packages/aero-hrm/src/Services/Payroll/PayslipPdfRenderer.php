<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Payroll;

use Aero\HRM\Models\Payslip;
use Illuminate\Http\Response;

/**
 * Payslip PDF renderer stub.
 *
 * dompdf is not yet installed. This stub returns a plain-text response so that
 * the payslip download endpoint is functional without a PDF library. Replace
 * the stream() body with a real PDF generation call once dompdf is available.
 */
final class PayslipPdfRenderer
{
    public function stream(Payslip $payslip): Response
    {
        return response(
            "Payslip #{$payslip->id} — PDF not yet configured",
            200,
            ['Content-Type' => 'text/plain'],
        );
    }
}
