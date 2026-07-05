<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Concerns;

use Aero\HRM\Models\LeaveApplication;

/**
 * Supplies the leave-module overview counts consumed by the shared LeaveRail
 * (rendered in the command shell's right rail on every Leave sub-page).
 */
trait ProvidesLeaveRailStats
{
    /**
     * @return array<string, int>
     */
    protected function leaveRailStats(): array
    {
        $counts = LeaveApplication::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return [
            'total'    => (int) $counts->sum(),
            'pending'  => (int) ($counts['pending'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }
}
