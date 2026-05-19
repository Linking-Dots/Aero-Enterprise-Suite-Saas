<?php

namespace Aero\HRM\Services\Safety;

use Aero\HRM\Models\HrmSafetyIncident;
use Aero\HRM\Models\HrmSafetyInspectionFinding;
use Illuminate\Support\Collection;

final class SafetyKpiService
{
    public function dashboard(): array
    {
        $hoursWorked = 200_000; // placeholder — will pull from attendance in future

        $lostTimeCount = HrmSafetyIncident::where('type', HrmSafetyIncident::TYPE_INJURY)
            ->whereYear('occurred_at', now()->year)
            ->count();

        $ltifr = $hoursWorked > 0
            ? round(($lostTimeCount * 1_000_000) / $hoursWorked, 2)
            : 0;

        // SQLite-safe: group by month in PHP
        $trend = HrmSafetyIncident::whereYear('occurred_at', now()->year)
            ->get(['occurred_at'])
            ->groupBy(fn ($i) => $i->occurred_at->format('Y-m'))
            ->map(fn (Collection $group, string $month) => ['month' => $month, 'total' => $group->count()])
            ->values();

        return [
            'ltifr'        => $ltifr,
            'openFindings' => HrmSafetyInspectionFinding::where('status', 'open')->count(),
            'incidentTrend'=> $trend,
            'totalThisYear'=> $trend->sum('total'),
        ];
    }
}
