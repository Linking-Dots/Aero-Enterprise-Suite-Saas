<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Analytics;

use Aero\HRM\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TurnoverAnalyticsService
{
    /**
     * Monthly turnover count for the past 12 months.
     *
     * @return Collection<int, array{month: string, count: int}>
     */
    public function monthlyTrend(): Collection
    {
        $since = now()->subMonths(12)->startOfMonth();

        return Employee::whereNotNull('date_of_leaving')
            ->whereDate('date_of_leaving', '>=', $since)
            ->get(['date_of_leaving'])
            ->groupBy(fn (Employee $e) => Carbon::parse($e->date_of_leaving)->format('Y-m'))
            ->map(fn (Collection $group, string $month) => [
                'month' => $month,
                'count' => $group->count(),
            ])
            ->sortKeys()
            ->values();
    }
}
