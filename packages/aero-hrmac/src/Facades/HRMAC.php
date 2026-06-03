<?php

declare(strict_types=1);

namespace Aero\HRMAC\Facades;

use Aero\Contracts\RoleModuleAccessInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool canAccessModule(\AeroHRMACModelsRole $role, int $moduleId)
 * @method static bool canAccessSubModule(\AeroHRMACModelsRole $role, int $subModuleId)
 * @method static bool canAccessComponent(\AeroHRMACModelsRole $role, int $componentId)
 * @method static bool canAccessAction(\AeroHRMACModelsRole $role, int $actionId)
 * @method static bool userCanAccessModule($user, string $moduleCode)
 * @method static bool userCanAccessSubModule($user, string $moduleCode, string $subModuleCode)
 * @method static string|null getFirstAccessibleRoute($user)
 * @method static array getAccessibleModuleIds(\AeroHRMACModelsRole $role)
 * @method static array getUserAccessibleSubModuleIds($user)
 * @method static void syncRoleAccess(\AeroHRMACModelsRole $role, array $accessData)
 * @method static array getRoleAccessTree(\AeroHRMACModelsRole $role)
 * @method static void clearRoleCache(\AeroHRMACModelsRole $role)
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
