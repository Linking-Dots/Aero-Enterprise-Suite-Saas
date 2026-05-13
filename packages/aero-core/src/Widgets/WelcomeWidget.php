<?php

declare(strict_types=1);

namespace Aero\Core\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Carbon\Carbon;

/**
 * Welcome Widget for Core Dashboard
 *
 * Shows personalized greeting and today's date.
 * This is a DISPLAY widget - informational only.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class WelcomeWidget extends AbstractDashboardWidget
{
    protected string $position = 'welcome';

    protected int $order = 1;

    protected int|string $span = 'full';

    protected CoreWidgetCategory $category = CoreWidgetCategory::DISPLAY;

    protected array $requiredPermissions = []; // No permissions needed - always visible

    public function getKey(): string
    {
        return 'core.welcome';
    }

    public function getComponent(): string
    {
        return 'Widgets/Core/WelcomeWidget';
    }

    public function getTitle(): string
    {
        return 'Welcome';
    }

    public function getDescription(): string
    {
        return 'Personalized greeting';
    }

    public function getModuleCode(): string
    {
        return 'core';
    }

    /**
     * Welcome widget is always enabled for authenticated users.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get widget data for frontend.
     */
    public function getData(): array
    {
        $user = auth()->user();
        $now = Carbon::now();
        $hour = $now->hour;

        // Determine greeting based on time of day
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour >= 12 && $hour < 17) {
            $greeting = 'Good Afternoon';
        } elseif ($hour >= 17 && $hour < 21) {
            $greeting = 'Good Evening';
        } else {
            $greeting = 'Hello';
        }

        return [
            'greeting' => $greeting,
            'userName' => $user?->name ?? 'User',
            'date' => $now->format('l, F j, Y'),
            'time' => $now->format('g:i A'),
        ];
    }
}
