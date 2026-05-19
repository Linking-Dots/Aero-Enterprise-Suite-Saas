<?php

namespace Aero\HRM\Http\Controllers\Expense;

use Aero\HRM\Http\Requests\Expense\RejectClaimRequest;
use Aero\HRM\Http\Requests\Expense\StoreClaimRequest;
use Aero\HRM\Models\HrmExpenseCategory;
use Aero\HRM\Models\HrmExpenseClaim;
use Aero\HRM\Services\Expense\ExpenseClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmExpenseClaimController extends Controller
{
    public function __construct(private ExpenseClaimService $svc) {}

    public function index(Request $r): Response
    {
        $claims = HrmExpenseClaim::with('employee:id,first_name,last_name')
            ->when($r->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($r->input('employee_id'), fn ($q, $v) => $q->where('employee_id', $v))
            ->when($r->input('q'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%")->orWhere('reference', 'like', "%{$v}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Expenses/Claims/Index', [
            'claims' => $claims,
            'filters' => $r->only(['status', 'employee_id', 'q']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Expenses/Claims/Create', [
            'categories' => HrmExpenseCategory::where('active', true)->orderBy('name')->get(['id', 'name']),
            'currencies' => ['USD', 'GBP', 'EUR', 'BDT'],
        ]);
    }

    public function store(StoreClaimRequest $r): RedirectResponse
    {
        $employee = $r->user()->employee;
        abort_unless($employee !== null, 422, 'No employee profile found. Contact HR.');
        $claim = $this->svc->submit((int) $employee->id, $r->validated());

        return redirect()->route('hrm.expenses.claims.show', $claim)->with('success', 'Expense claim submitted.');
    }

    public function show(HrmExpenseClaim $claim): Response
    {
        $claim->load(['items.category', 'receipts', 'employee', 'reviewer:id,name']);

        return Inertia::render('HRM/Expenses/Claims/Show', [
            'claim' => $claim,
            'can' => [
                'approve' => auth()->user()->can('hrm.expenses.expense-claims.approve'),
            ],
        ]);
    }

    public function approve(Request $r, HrmExpenseClaim $claim): RedirectResponse
    {
        $this->svc->approve($claim, (int) $r->user()->id);

        return back()->with('success', 'Claim approved.');
    }

    public function reject(RejectClaimRequest $r, HrmExpenseClaim $claim): RedirectResponse
    {
        $this->svc->reject($claim, (int) $r->user()->id, $r->validated('reason'));

        return back()->with('success', 'Claim rejected.');
    }
}
