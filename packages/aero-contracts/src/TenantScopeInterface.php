<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface TenantScopeInterface
{
    public function getCurrentTenantId(): int|string|null;
    public function getCurrentTenant(): mixed;
    public function inTenantContext(): bool;
    public function inCentralContext(): bool;
    public function getMode(): string;
    public function isSaaSMode(): bool;
    public function isStandaloneMode(): bool;
}
