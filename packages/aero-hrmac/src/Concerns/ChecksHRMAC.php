<?php

declare(strict_types=1);

namespace Aero\HRMAC\Concerns;

use Aero\HRMAC\Facades\HRMAC;
use Illuminate\Contracts\Auth\Authenticatable;
use Aero\HRMAC\Models\ModuleComponentAction;

/**
 * Trait ChecksHRMAC
 *
 * HRMAC-based policy trait that replaces the legacy ChecksModuleAccess trait.
 * Delegates all access checks to the HRMAC Facade/Service.
 *
 * Super admin bypass is handled automatically by the HRMAC service —
 * no need to check isSuperAdmin() manually in policy methods.
 *
 * Usage in policies:
 * ```php
 * use Aero\HRMAC\Concerns\ChecksHRMAC;
 *
 * class EmployeePolicy
 * {
 *     use ChecksHRMAC;
 *
 *     public function viewAny(Authenticatable $user): bool
 *     {
 *         return $this->canPerformAction($user, 'hrm', 'employees', 'employee-list', 'view');
 *     }
 * }
 * ```
 */
trait ChecksHRMAC
{
    /**
     * Check if user is Super Administrator (bypasses all checks).
     *
     * Note: HRMAC service handles super admin bypass automatically.
     * This method is provided for backward compatibility and edge cases
     * where policies need explicit ownership/super-admin logic.
     */
    protected function isSuperAdmin(Authenticatable $user): bool
    {
        $superAdminRoles = config('hrmac.super_admin_roles', ['Super Administrator', 'tenant_super_administrator']);

        foreach ($superAdminRoles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can access a module.
     */
    protected function canAccessModule(Authenticatable $user, string $moduleCode): bool
    {
        return HRMAC::userCanAccessModule($user, $moduleCode);
    }

    /**
     * Check if user can access a submodule.
     */
    protected function canAccessSubModule(Authenticatable $user, string $moduleCode, string $subModuleCode): bool
    {
        return HRMAC::userCanAccessSubModule($user, $moduleCode, $subModuleCode);
    }

    /**
     * Check if user can perform an action.
     *
     * Maintains backward compatibility with the 5-parameter signature from
     * the legacy ChecksModuleAccess trait. The component parameter is accepted
     * but the HRMAC service resolves actions at the submodule level.
     *
     * @param  Authenticatable  $user  The user to check
     * @param  string  $moduleCode  The module code (e.g., 'hrm')
     * @param  string  $subModuleCode  The submodule code (e.g., 'employees')
     * @param  string  $componentCode  The component code (accepted for compatibility)
     * @param  string  $actionCode  The action code (e.g., 'create', 'view', 'update', 'delete')
     */
    protected function canPerformAction(
        Authenticatable $user,
        string $moduleCode,
        string $subModuleCode,
        string $componentCode,
        string $actionCode
    ): bool {
        return HRMAC::userCanAccessAction($user, $moduleCode, $subModuleCode, $actionCode);
    }

    /**
     * Check if user can access their own resources (for own-scope access).
     */
    protected function isOwner(Authenticatable $user, mixed $model, string $ownerField = 'user_id'): bool
    {
        if (! isset($model->$ownerField)) {
            return false;
        }

        return $user->id === $model->$ownerField;
    }

    /**
     * Check if user is in the same department as the model.
     */
    protected function isSameDepartment(Authenticatable $user, mixed $model): bool
    {
        $userDepartmentId = $user->employee?->department_id;

        if (! $userDepartmentId) {
            return false;
        }

        if (isset($model->department_id)) {
            return $userDepartmentId === $model->department_id;
        }

        if (isset($model->user) && isset($model->user->employee)) {
            return $userDepartmentId === $model->user->employee->department_id;
        }

        return false;
    }

    /**
     * Check access with scope consideration.
     *
     * Checks both HRMAC action access AND scope-based restrictions.
     */
    protected function canPerformActionWithScope(
        Authenticatable $user,
        string $moduleCode,
        string $subModuleCode,
        string $componentCode,
        string $actionCode,
        ?object $model = null
    ): bool {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if (! HRMAC::userCanAccessAction($user, $moduleCode, $subModuleCode, $actionCode)) {
            return false;
        }

        if ($model === null) {
            return true;
        }

        $actionRecord = ModuleComponentAction::where('code', $actionCode)
            ->where('is_active', true)
            ->whereHas('component', function ($q) use ($componentCode, $subModuleCode, $moduleCode) {
                $q->where('code', $componentCode)
                    ->where('is_active', true)
                    ->whereHas('subModule', function ($q2) use ($subModuleCode, $moduleCode) {
                        $q2->where('code', $subModuleCode)
                            ->where('is_active', true)
                            ->whereHas('module', function ($q3) use ($moduleCode) {
                                $q3->where('code', $moduleCode)->where('is_active', true);
                            });
                    });
            })->first();

        if (! $actionRecord) {
            return true;
        }

        $scope = $this->resolveUserScope($user, $actionRecord->id);

        return match ($scope) {
            'all' => true,
            'department' => $this->isSameDepartment($user, $model),
            'team' => $this->isSameDepartment($user, $model),
            'own' => $this->isOwner($user, $model),
            default => false,
        };
    }

    /**
     * Resolve the user's access scope for a specific action.
     */
    protected function resolveUserScope(Authenticatable $user, int $actionId): string
    {
        foreach ($user->roles as $role) {
            $access = \Aero\HRMAC\Models\RoleModuleAccess::where('role_id', $role->id)
                ->where('module_component_action_id', $actionId)
                ->where('has_access', true)
                ->first();

            if ($access) {
                return $access->access_scope ?? 'all';
            }
        }

        return 'own';
    }
}
