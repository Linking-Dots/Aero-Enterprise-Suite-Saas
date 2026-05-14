<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface NavigationRegistryInterface
{
    public function register(string $moduleCode, array $items, int $priority = 100, string $scope = 'tenant'): void;

    public function registerSelfService(string $moduleCode, array $items, int $priority = 100): void;
}
