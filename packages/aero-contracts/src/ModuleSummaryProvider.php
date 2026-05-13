<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface ModuleSummaryProvider
{
    /**
     * @return array{key: string, label: string, icon: string, stats: array<string, mixed>, alerts: array<int, string>, pendingCount: int}
     */
    public function getDashboardSummary(): array;
}
