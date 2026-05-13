<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface ProductAccessInterface
{
    public function tenantCanAccessModule(string $tenantId, string $moduleCode): bool;
    public function getAccessibleModuleCodes(string $tenantId): array;
    public function flushCache(string $tenantId): void;
}
