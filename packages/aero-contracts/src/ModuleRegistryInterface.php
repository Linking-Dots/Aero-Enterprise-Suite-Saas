<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface ModuleRegistryInterface
{
    public function register(ModuleProviderInterface $provider): void;
}
