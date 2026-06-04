<?php

declare(strict_types=1);

namespace Aero\HRMAC\Services;

use Aero\Contracts\RoleModuleAccessInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\HRMAC\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Role lifecycle service (HRMAC). Roles carry no Spatie permissions — authorization
 * is granted via module-access (role_module_access) through the module-access editor
 * (RoleModuleAccessService). Every mutation is wrapped in a transaction and audited.
 */
class RoleService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data, Model $actor): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'web',
                'description' => $data['description'] ?? null,
                'default_dashboard' => $data['default_dashboard'] ?? null,
                'priority' => $data['priority'] ?? 0,
                // roles.scope is NOT NULL; default to 'tenant' (the common case) when
                // the caller doesn't specify. Consumers may pass 'platform' explicitly.
                'scope' => $data['scope'] ?? 'tenant',
            ]);

            $this->audit->log(
                AuditEventType::RECORD_CREATED->value,
                'created',
                null,
                'Role created',
                null,
                null,
                ['role' => $role->name]
            );

            return $role;
        });
    }

    public function update(Role $role, array $data, Model $actor): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'default_dashboard' => $data['default_dashboard'] ?? null,
                'priority' => $data['priority'] ?? null,
                'scope' => $data['scope'] ?? null,
            ], fn ($v) => $v !== null));

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'updated',
                null,
                'Role updated',
                null,
                null,
                ['role' => $role->name]
            );

            return $role->fresh();
        });
    }

    public function delete(Role $role, Model $actor): void
    {
        DB::transaction(function () use ($role) {
            $this->audit->log(
                AuditEventType::RECORD_DELETED->value,
                'deleted',
                null,
                'Role deleted',
                null,
                null,
                ['role' => $role->name]
            );
            // Role::deleting hook cleans module_access + model_has_roles.
            $role->delete();
        });
    }

    /**
     * Assign a set of roles to a user (replaces the user's current role set).
     *
     * Accepts any user model (tenant User or central LandlordUser) — the morph class
     * + key come from the model, and model_has_roles is written on the default
     * connection (tenant or central, as the host context dictates).
     */
    public function assignToUser(Model $user, array $roleIds, ?Model $actor = null): void
    {
        DB::transaction(function () use ($user, $roleIds) {
            $userModel = $user->getMorphClass();

            DB::table('model_has_roles')
                ->where('model_type', $userModel)
                ->where('model_id', $user->getKey())
                ->delete();

            foreach (array_unique($roleIds) as $roleId) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => $userModel,
                    'model_id' => $user->getKey(),
                ]);
            }

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'roles_assigned',
                null,
                'Roles assigned to user',
                null,
                null,
                ['user_id' => $user->getKey(), 'roles' => array_values(array_unique($roleIds))]
            );
        });
    }

    /**
     * Sync a role's module-access grants (HRMAC) — replaces Spatie syncPermissions.
     * Delegates to the RoleModuleAccessService; the detailed grant editor lives in
     * the module-access surface (ModuleController).
     */
    public function syncModuleAccess(Role $role, array $grants, Model $actor): void
    {
        DB::transaction(function () use ($role, $grants) {
            app(RoleModuleAccessInterface::class)->syncRoleAccess($role, [
                'modules' => $grants['modules'] ?? [],
                'sub_modules' => $grants['sub_modules'] ?? [],
                'components' => $grants['components'] ?? [],
                'actions' => $grants['actions'] ?? [],
            ]);

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'module_access_synced',
                null,
                'Role module access synced',
                null,
                null,
                ['role' => $role->name]
            );
        });
    }
}
