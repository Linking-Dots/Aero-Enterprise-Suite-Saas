<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface SmsContextResolverInterface
{
    /**
     * Resolve current SMS configuration.
     *
     * @return array{configured: bool, provider: string, credentials: array}
     */
    public function resolve(): array;
}
