<?php

declare(strict_types=1);

namespace Aero\Contracts;

enum CoreWidgetCategory: string
{
    case ACTION     = 'action';
    case ALERT      = 'alert';
    case SUMMARY    = 'summary';
    case NAVIGATION = 'navigation';
    case FEED       = 'feed';
    case DISPLAY    = 'display';

    public function isValidForCoreDashboard(): bool
    {
        return true;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTION     => 'Action Required',
            self::ALERT      => 'Alerts',
            self::SUMMARY    => 'Quick Stats',
            self::NAVIGATION => 'Navigation',
            self::FEED       => 'Activity Feed',
            self::DISPLAY    => 'Information',
        };
    }

    public function getRecommendedPosition(): string
    {
        return match ($this) {
            self::ACTION     => 'main_left',
            self::ALERT      => 'sidebar',
            self::SUMMARY    => 'stats_row',
            self::NAVIGATION => 'main_left',
            self::FEED       => 'main_right',
            self::DISPLAY    => 'welcome',
        };
    }
}
