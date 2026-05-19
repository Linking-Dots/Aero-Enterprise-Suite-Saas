<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Settings;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\HrmPublicHoliday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmPublicHolidayController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $request): Response
    {
        $year = (int) $request->query('year', (string) now()->year);

        $holidays = HrmPublicHoliday::query()
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get();

        return Inertia::render('HRM/Settings/Holidays/Index', [
            'holidays' => $holidays,
            'year' => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'is_optional' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            $holiday = HrmPublicHoliday::create($validated);

            $this->audit->log(
                event: 'SETTINGS_UPDATED',
                action: 'create',
                subject: null,
                description: "Created public holiday: {$holiday->name} on {$holiday->date->toDateString()}",
            );
        });

        return back()->with('success', 'Holiday added.');
    }

    public function destroy(HrmPublicHoliday $holiday): RedirectResponse
    {
        DB::transaction(function () use ($holiday) {
            $this->audit->log(
                event: 'SETTINGS_UPDATED',
                action: 'delete',
                subject: null,
                description: "Deleted public holiday: {$holiday->name} on {$holiday->date->toDateString()}",
            );

            $holiday->delete();
        });

        return back()->with('success', 'Holiday removed.');
    }
}
