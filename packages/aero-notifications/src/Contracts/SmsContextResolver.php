<?php

declare(strict_types=1);

namespace Aero\Notifications\Contracts;

interface SmsContextResolver
{
    /**
     * Resolve current SMS configuration.
     *
     * @return array{
     *     configured: bool,
     *     provider: string,
     *     credentials: array<string, mixed>,
     * }
     */
    public function resolve(): array;
}
