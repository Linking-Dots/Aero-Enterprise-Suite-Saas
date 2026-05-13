<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

/**
 * Role Module Access Interface
 *
 * Defines the contract for role-based module access operations.
 * Implementations can target tenant or landlord databases.
 */
interface RoleModuleAccessInterface
{
    public function canAccessModule(mixed $role, int $moduleId): bool;

    public function canAccessSubModule(mixed $role, int $subModuleId): bool;

    public function canAccessComponent(mixed $role, int $componentId): bool;

    public function canAccessAction(mixed $role, int $actionId): bool;

    public function userCanAccessModule(mixed $user, string $moduleCode): bool;

    public function userCanAccessSubModule(mixed $user, string $moduleCode, string $subModuleCode): bool;

    public function userCanAccessAction(mixed $user, string $moduleCode, string $subModuleCode, string $actionCode): bool;

    public function getFirstAccessibleRoute(mixed $user): ?string;

    public function getAccessibleModuleIds(mixed $role): array;

    public function getUserAccessibleSubModuleIds(mixed $user): array;

    public function syncRoleAccess(mixed $role, array $accessData): void;

    public function getRoleAccessTree(mixed $role): array;

    public function clearRoleCache(mixed $role): void;

    public function clearUserCache(mixed $user): void;

    /** @return Collection Collection of User models */
    public function getUsersWithSubModuleAccess(string $moduleCode, string $subModuleCode, ?string $actionCode = null): Collection;

    /** @return Collection Collection of User models */
    public function getUsersWithActionAccess(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): Collection;
}
