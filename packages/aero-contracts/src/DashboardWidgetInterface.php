<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface DashboardWidgetInterface
{
    public function getKey(): string;
    public function getComponent(): string;
    public function getTitle(): string;
    public function getDescription(): string;
    public function getCategory(): CoreWidgetCategory;
    public function getPosition(): string;
    public function getOrder(): int;
    public function getSpan(): int|string;
    public function isLazy(): bool;
    public function isEnabled(): bool;
    public function getData(): array;
    public function getModuleCode(): string;
    public function getRequiredPermissions(): array;
    public function getDashboards(): array;
}
