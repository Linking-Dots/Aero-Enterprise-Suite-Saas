<?php

declare(strict_types=1);

namespace Aero\HRM\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Pending Reviews Widget
 *
 * Displays pending performance reviews for managers.
 *
 * Appears on: HRM Manager Dashboard (/hrm/dashboard)
 */
class PendingReviewsWidget extends AbstractDashboardWidget
{
    protected string $position = 'sidebar';

    protected int $order = 80;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ACTION;

    protected array $requiredPermissions = ['hrm.performance.reviews.view'];

    protected array $dashboards = ['hrm'];

    public function getKey(): string
    {
        return 'hrm.pending_reviews';
    }

    public function getComponent(): string
    {
        return 'Widgets/HRM/PendingReviewsWidget';
    }

    public function getTitle(): string
    {
        return 'Pending Reviews';
    }

    public function getDescription(): string
    {
        return 'Performance reviews awaiting your action';
    }

    public function getModuleCode(): string
    {
        return 'hrm';
    }

    public function getData(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'reviews' => [],
                'self_assessments_due' => 0,
                'peer_reviews_due' => 0,
                'manager_reviews_due' => 0,
            ];
        }

        // In production, query from PerformanceReview model
        return [
            'reviews' => [],
            'self_assessments_due' => 0,
            'peer_reviews_due' => 0,
            'manager_reviews_due' => 0,
        ];
    }

    public function getProps(): array
    {
        return array_merge($this->getData(), [
            'title' => $this->getTitle(),
            'view_all_url' => route('hrm.performance.index', [], false),
        ]);
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

        if (! $this->isModuleActive()) {
            return false;
        }

        // Check HRM performance module access via HRMAC
        return $this->userHasModuleAccess();
    }

    public function getPriority(): int
    {
        return 80;
    }
}
