<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Payroll;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\TaxBracket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaxSettingController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(): Response
    {
        Gate::authorize('hrmac', 'hrm.payroll.tax-setup.view');

        $year = (int) now()->year;

        $brackets = TaxBracket::where('effective_year', $year)
            ->orderBy('income_from')
            ->get()
            ->map(fn (TaxBracket $b) => [
                'id' => $b->id,
                'country_code' => $b->country_code,
                'income_from' => $b->income_from,
                'income_to' => $b->income_to,
                'rate' => $b->rate,
                'effective_year' => $b->effective_year,
            ]);

        return Inertia::render('HRM/Payroll/Settings/Tax', [
            'brackets' => $brackets,
            'currentYear' => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.payroll.tax-setup.manage');

        $data = $request->validate([
            'brackets' => ['required', 'array', 'min:1'],
            'brackets.*.country_code' => ['required', 'string', 'max:5'],
            'brackets.*.income_from' => ['required', 'numeric', 'min:0'],
            'brackets.*.income_to' => ['nullable', 'numeric', 'gt:brackets.*.income_from'],
            'brackets.*.rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'brackets.*.effective_year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        DB::transaction(function () use ($data): void {
            foreach ($data['brackets'] as $bracket) {
                TaxBracket::updateOrCreate(
                    [
                        'country_code' => $bracket['country_code'],
                        'income_from' => $bracket['income_from'],
                        'effective_year' => $bracket['effective_year'],
                    ],
                    [
                        'income_to' => $bracket['income_to'] ?? null,
                        'rate' => $bracket['rate'],
                    ]
                );
            }

            $this->audit->log(
                event: AuditEventType::RECORD_UPDATED->value,
                action: 'tax_brackets_upserted',
                subject: null,
                description: 'Tax brackets updated: '.count($data['brackets']).' bracket(s).',
                after: ['count' => count($data['brackets'])],
            );
        });

        return back();
    }
}
