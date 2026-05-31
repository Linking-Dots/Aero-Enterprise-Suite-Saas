<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Api;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Payslip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Audit D24 — Payslip REST API (mobile self-service).
 *
 * Default scope is OWN: the token holder sees only their own payslips
 * (resolved via Employee.user_id). Admins with hrm.payroll.payslips.list.view
 * can override the scope to view any payslip by ID.
 *
 * Bank fields are EncryptedField casts on the model — never returned in full.
 * Only bank_last_four is exposed (last 4 of the masked account number).
 */
class PayslipApiController extends Controller
{
    /**
     * GET /api/hrm/payslips
     * Returns the authenticated user's own payslips, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $employee = Employee::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $employee) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 0, 'total' => 0],
                'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
            ]);
        }

        $payslips = Payslip::query()
            ->where('employee_id', $employee->id)
            ->with('run:id,period_start,period_end,paid_at,is_locked')
            ->orderByDesc('id')
            ->paginate($this->boundedPerPage($request, 20, 100))
            ->withQueryString();

        return response()->json([
            'data' => array_map(fn (Payslip $p) => $this->transform($p), $payslips->items()),
            'meta' => [
                'current_page' => $payslips->currentPage(),
                'last_page'    => $payslips->lastPage(),
                'per_page'     => $payslips->perPage(),
                'total'        => $payslips->total(),
            ],
            'links' => [
                'first' => $payslips->url(1),
                'last'  => $payslips->url($payslips->lastPage()),
                'prev'  => $payslips->previousPageUrl(),
                'next'  => $payslips->nextPageUrl(),
            ],
        ]);
    }

    /**
     * GET /api/hrm/payslips/{payslip}
     *
     * Owner can view their own; admins with hrm.payroll.payslips.list.view
     * can view any.
     */
    public function show(Payslip $payslip, Request $request): JsonResponse
    {
        $employee = Employee::query()
            ->where('user_id', $request->user()->id)
            ->first();

        $isOwner = $employee !== null && (int) $payslip->employee_id === (int) $employee->id;

        if (! $isOwner) {
            $this->authorize('hrm.payroll.payslips.list.view');
        }

        $payslip->load('run:id,period_start,period_end,paid_at,is_locked');

        return response()->json([
            'data' => $this->transformDetailed($payslip),
        ]);
    }

    protected function transform(Payslip $p): array
    {
        return [
            'id'               => $p->id,
            'payroll_run_id'   => $p->payroll_run_id,
            'period_start'     => optional($p->run?->period_start)->toDateString(),
            'period_end'       => optional($p->run?->period_end)->toDateString(),
            'gross'            => $p->gross,
            'tax'              => $p->tax,
            'deductions_total' => $p->deductions_total,
            'net'              => $p->net,
            'paid_at'          => optional($p->run?->paid_at)->toDateString(),
            'is_locked'        => (bool) $p->run?->is_locked,
        ];
    }

    protected function transformDetailed(Payslip $p): array
    {
        return array_merge($this->transform($p), [
            'line_items'     => $p->line_items,
            // Bank fields are encrypted via EncryptedField; only expose last 4 of account.
            'bank_last_four' => $p->bank_account_number ? substr((string) $p->bank_account_number, -4) : null,
            'bank_name'      => $p->bank_name ? '****' : null,
        ]);
    }
}
