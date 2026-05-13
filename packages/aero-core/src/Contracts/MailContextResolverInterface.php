<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface MailContextResolverInterface
{
    /**
     * Resolve current mail configuration array.
     *
     * @return array{configured: bool, driver: string, from_address: string, from_name: string}
     */
    public function resolve(): array;
}
