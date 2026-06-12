<?php

declare(strict_types=1);

namespace Aero\HRMAC\Services;

use Aero\Contracts\RoleModuleAccessInterface;
use Illuminate\Support\Collection;

/**
 * Null Object implementation of the RoleModuleAccess contract.
 *
 * Used as a pre-installation / no-DB stub so the application can satisfy the
 * RoleModuleAccessInterface type hint throughout the container WITHOUT making
 * any database queries. Every method returns a safe "deny-all / empty" default
 * so RBAC fails CLOSED before the system is installed.
 *
 * This lives in aero-hrmac (the RBAC owner) — NOT in any consumer tier — so the
 * fail-safe ships with the authority it stands in for. HRMAC is context-free:
 * this class references no Aero\Core / Aero\Platform symbol.
 */
class NullRoleModuleAccessService implements RoleModuleAccessInterface
{
    public function canAccessModule(mixed $role, int $moduleId): bool
    {
        return false;
    }

    public function canAccessSubModule(mixed $role, int $subModuleId): bool
    {
        return false;
    }

    public function canAccessComponent(mixed $role, int $componentId): bool
    {
        return false;
    }

    public function canAccessAction(mixed $role, int $actionId): bool
    {
        return false;
    }

    public function userCanAccessModule(mixed $user, string $moduleCode): bool
    {
        return false;
    }

    public function userCanAccessSubModule(mixed $user, string $moduleCode, string $subModuleCode): bool
    {
        return false;
    }

    public function userCanAccessAction(mixed $user, string $moduleCode, string $subModuleCode, string $actionCode): bool
    {
        return false;
    }

    public function getFirstAccessibleRoute(mixed $user): ?string
    {
        return null;
    }

    public function getAccessibleModuleIds(mixed $role): array
    {
        return [];
    }

    public function getUserAccessibleSubModuleIds(mixed $user): array
    {
        return [];
    }

    public function syncRoleAccess(mixed $role, array $accessData): void
    {
        // No-op before installation.
    }

    public function getRoleAccessTree(mixed $role): array
    {
        return [
            'modules' => [],
            'explicit_modules' => [],
            'sub_modules' => [],
            'components' => [],
            'actions' => [],
        ];
    }

    public function clearRoleCache(mixed $role): void
    {
        // No-op before installation.
    }

    public function clearUserCache(mixed $user): void
    {
        // No-op before installation.
    }

    public function getUsersWithSubModuleAccess(string $moduleCode, string $subModuleCode, ?string $actionCode = null): Collection
    {
        return collect();
    }

    public function getUsersWithActionAccess(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): Collection
    {
        return collect();
    }
}
