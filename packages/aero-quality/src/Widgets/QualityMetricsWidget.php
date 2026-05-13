<?php

declare(strict_types=1);

namespace Aero\Quality\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Quality Metrics Widget
 *
 * Displays key quality performance indicators.
 * This is a SUMMARY widget showing quality metrics.
 *
 * Appears on: Quality Dashboard (/quality/dashboard)
 */
class QualityMetricsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 20;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['quality.reports'];

    protected array $dashboards = ['quality'];

    public function getKey(): string
    {
        return 'quality.metrics';
    }

    public function getComponent(): string
    {
        return 'Widgets/Quality/QualityMetricsWidget';
    }

    public function getTitle(): string
    {
        return 'Quality Metrics';
    }

    public function getDescription(): string
    {
        return 'Key quality performance indicators';
    }

    public function getModuleCode(): string
    {
        return 'quality';
    }

    public function isEnabled(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isModuleActive()) {
            return false;
        }

        return $this->userHasModuleAccess();
    }

    public function getData(): array
    {
        return $this->safeResolve(function () {
            $user = auth()->user();
            if (! $user) {
                return $this->getEmptyState();
            }

            // TODO: Implement real metrics calculation
            return [
                'ncr_rate' => 2.5,
                'capa_closure_rate' => 85,
                'first_pass_yield' => 97,
                'audit_score' => 88,
                'trends' => [
                    'ncr_rate_trend' => 'down',
                    'closure_rate_trend' => 'up',
                ],
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'ncr_rate' => 0,
            'capa_closure_rate' => 0,
            'first_pass_yield' => 0,
            'audit_score' => 0,
            'message' => 'No quality data available',
        ];
    }
}
