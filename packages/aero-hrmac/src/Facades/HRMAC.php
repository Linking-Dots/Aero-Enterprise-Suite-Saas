<?php

declare(strict_types=1);

namespace Aero\HRMAC\Facades;

use Aero\Contracts\RoleModuleAccessInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool canAccessModule(\Aero\HRMAC\Models\Role $role, int $moduleId)
 * @method static bool canAccessSubModule(\Aero\HRMAC\Models\Role $role, int $subModuleId)
 * @method static bool canAccessComponent(\Aero\HRMAC\Models\Role $role, int $componentId)
 * @method static bool canAccessAction(\Aero\HRMAC\Models\Role $role, int $actionId)
 * @method static bool userCanAccessModule($user, string $moduleCode)
 * @method static bool userCanAccessSubModule($user, string $moduleCode, string $subModuleCode)
 * @method static string|null getFirstAccessibleRoute($user)
 * @method static array getAccessibleModuleIds(\Aero\HRMAC\Models\Role $role)
 * @method static array getUserAccessibleSubModuleIds($user)
 * @method static void syncRoleAccess(\Aero\HRMAC\Models\Role $role, array $accessData)
 * @method static array getRoleAccessTree(\Aero\HRMAC\Models\Role $role)
 * @method static void clearRoleCache(\Aero\HRMAC\Models\Role $role)
 * @method static void clearUserCache($user)
 * @method static \Illuminate\Support\Collection getUsersWithSubModuleAccess(string $moduleCode, string $subModuleCode, ?string $actionCode = null)
 * @method static \Illuminate\Support\Collection getUsersWithActionAccess(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode)
 *
 * @see \Aero\HRMAC\Services\RoleModuleAccessService
 */
class HRMAC extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return RoleModuleAccessInterface::class;
    }
}
