<?php

namespace Aero\HRM\Http\Controllers\Expense;

use Aero\HRM\Models\HrmExpenseClaim;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmMyExpenseController extends Controller
{
    public function index(Request $r): Response
    {
        $claims = HrmExpenseClaim::where('employee_id', $r->user()->employee->id)
            ->when($r->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Expenses/MyClaims/Index', [
            'claims' => $claims,
            'filters' => $r->only(['status']),
        ]);
    }
}
