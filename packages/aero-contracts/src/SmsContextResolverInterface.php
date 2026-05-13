<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface SmsContextResolverInterface
{
    /**
     * @return array{configured: bool, provider: string, credentials: array}
     */
    public function resolve(): array;
}
