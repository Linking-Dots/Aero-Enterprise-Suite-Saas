<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Attendance;

use Aero\HRM\Models\Timesheet;

final class TimesheetAggregator
{
    /**
     * Recompute the total_hours for a timesheet by summing its entries.
     */
    public function recompute(Timesheet $timesheet): void
    {
        $timesheet->total_hours = (float) $timesheet->entries()->sum('hours');
        $timesheet->save();
    }
}
