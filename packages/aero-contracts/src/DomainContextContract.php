<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface DomainContextContract
{
    public const CONTEXT_ADMIN      = 'admin';
    public const CONTEXT_PLATFORM   = 'platform';
    public const CONTEXT_TENANT     = 'tenant';
    public const CONTEXT_STANDALONE = 'standalone';

    public function getContext(): string;
    public function isAdminContext(): bool;
    public function isPlatformContext(): bool;
    public function isTenantContext(): bool;
    public function isCentralContext(): bool;
}
