<?php

declare(strict_types=1);

namespace Aero\HRM\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upcoming Holidays Widget
 *
 * Displays upcoming company holidays:
 * - Holiday name
 * - Date
 * - Days remaining
 *
 * This is a DISPLAY widget - static information.
 * Appears on: HRM Employee Dashboard (/hrm/employee/dashboard)
 */
class UpcomingHolidaysWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 3;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::DISPLAY;

    protected array $requiredPermissions = [];

    protected array $dashboards = ['hrm.employee'];

    public function getKey(): string
    {
        return 'hrm.upcoming_holidays';
    }

    public function getComponent(): string
    {
        return 'Widgets/HRM/UpcomingHolidaysWidget';
    }

    public function getTitle(): string
    {
        return 'Upcoming Holidays';
    }

    public function getDescription(): string
    {
        return 'Next holidays and days off';
    }

    public function getModuleCode(): string
    {
        return 'hrm';
    }

    /**
     * Check if widget is enabled.
     * Super Administrators bypass ALL checks.
     */
    public function isEnabled(): bool
    {
        // Super Admin bypass - always enabled, bypasses ALL checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Display widget - only requires module to be active
        return $this->isModuleActive();
    }

    /**
     * Get widget data for frontend.
     */
    public function getData(): array
    {
        $holidays = [];

        try {
            if (Schema::hasTable('holidays')) {
                $upcomingHolidays = DB::table('holidays')
                    ->where('date', '>=', today())
                    ->orderBy('date')
                    ->limit(5)
                    ->get(['id', 'name', 'date', 'type']);

                foreach ($upcomingHolidays as $holiday) {
                    $holidayDate = \Carbon\Carbon::parse($holiday->date);
                    $daysRemaining = now()->diffInDays($holidayDate, false);

                    $holidays[] = [
                        'id' => $holiday->id,
                        'name' => $holiday->name,
                        'date' => $holiday->date,
                        'dateFormatted' => $holidayDate->format('M j'),
                        'dayName' => $holidayDate->format('l'),
                        'daysRemaining' => max(0, (int) $daysRemaining),
                        'type' => $holiday->type ?? 'public',
                        'isToday' => $holidayDate->isToday(),
                        'isTomorrow' => $holidayDate->isTomorrow(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // If no holidays, show message
        if (empty($holidays)) {
            $holidays = [
                [
                    'id' => 0,
                    'name' => 'No upcoming holidays',
                    'date' => null,
                    'dateFormatted' => '-',
                    'dayName' => '-',
                    'daysRemaining' => null,
                    'type' => 'info',
                    'isToday' => false,
                    'isTomorrow' => false,
                ],
            ];
        }

        return [
            'holidays' => $holidays,
            'count' => count($holidays),
        ];
    }
}
