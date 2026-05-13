<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface MailContextResolverInterface
{
    /**
     * @return array{configured: bool, driver: string, from_address: string, from_name: string}
     */
    public function resolve(): array;
}
