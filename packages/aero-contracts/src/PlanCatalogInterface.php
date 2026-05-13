<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

interface PlanCatalogInterface
{
    public function getPlansForModule(string $moduleCode): Collection;
    public function isModuleInAnyPlan(string $moduleCode): bool;
}
