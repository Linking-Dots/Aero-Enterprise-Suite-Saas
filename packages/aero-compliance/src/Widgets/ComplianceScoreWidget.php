<?php

declare(strict_types=1);

namespace Aero\Compliance\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Compliance Score Widget
 *
 * Displays overall compliance status and score.
 * This is a SUMMARY widget showing compliance health.
 *
 * Appears on: Compliance Dashboard (/compliance/dashboard)
 */
class ComplianceScoreWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 20;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['compliance.reports'];

    protected array $dashboards = ['compliance'];

    public function getKey(): string
    {
        return 'compliance.score';
    }

    public function getComponent(): string
    {
        return 'Widgets/Compliance/ComplianceScoreWidget';
    }

    public function getTitle(): string
    {
        return 'Compliance Score';
    }

    public function getDescription(): string
    {
        return 'Overall compliance status and score';
    }

    public function getModuleCode(): string
    {
        return 'compliance';
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

            // TODO: Calculate real compliance score from models
            return [
                'score' => 85,
                'grade' => 'B+',
                'compliant_items' => 42,
                'non_compliant_items' => 8,
                'pending_review' => 5,
                'trend' => 'up',
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'score' => 0,
            'grade' => 'N/A',
            'compliant_items' => 0,
            'non_compliant_items' => 0,
            'pending_review' => 0,
            'message' => 'No compliance data available',
        ];
    }
}
