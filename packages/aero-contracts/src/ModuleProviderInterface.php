<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface ModuleProviderInterface
{
    public function getModuleCode(): string;
    public function getModuleName(): string;
    public function getModuleDescription(): string;
    public function getModuleVersion(): string;
    public function getModuleCategory(): string;
    public function getModuleIcon(): string;
    public function getModulePriority(): int;
    public function getModuleHierarchy(): array;
    public function getNavigationItems(): array;
    public function getRoutes(): array;
    public function getDependencies(): array;
    public function isEnabled(): bool;
    public function getMinimumPlan(): ?string;
    public function register(): void;
    public function boot(): void;
}
