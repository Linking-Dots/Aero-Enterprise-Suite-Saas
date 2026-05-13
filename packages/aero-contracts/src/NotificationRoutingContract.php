<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

interface NotificationRoutingContract
{
    public function getRecipients(
        string $moduleCode,
        string $subModuleCode,
        ?string $componentCode = null,
        ?string $actionCode = null,
        array $context = []
    ): Collection;

    public function getRecipientsByScope(
        string $moduleCode,
        string $subModuleCode,
        string $scope,
        array $context
    ): Collection;

    public function shouldNotify(
        int $userId,
        string $moduleCode,
        string $subModuleCode,
        ?string $componentCode = null,
        ?string $actionCode = null
    ): bool;
}
