<?php

declare(strict_types=1);

namespace Aero\Core\Services;

use Aero\Core\Models\User;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\HRMAC\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data, User $actor): Role
    {
        return DB::transaction(function () use ($data) {
            // HRMAC role: name + guard only. Authorization is granted via
            // module-access (role_module_access) through the module-access editor
            // (RoleModuleAccessService), not Spatie permissions.
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'web',
                'display_name' => $data['display_name'] ?? null,
                'description' => $data['description'] ?? null,
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

    public function update(Role $role, array $data, User $actor): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(array_filter([
                'name' => $data['name'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'description' => $data['description'] ?? null,
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

    public function delete(Role $role, User $actor): void
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
            $role->delete();
        });
    }

    /**
     * Sync a role's module-access grants (HRMAC). `$grants` is the desired set of
     * sub-module / component / action IDs the role should have, delegated to the
     * HRMAC RoleModuleAccessService (replaces Spatie syncPermissions). The detailed
     * grant editor UI is wired in the consolidation phase (P-D); this keeps the
     * endpoint Spatie-free and functional.
     */
    public function syncModulePermissions(Role $role, array $grants, User $actor): void
    {
        DB::transaction(function () use ($role, $grants) {
            /** @var \Aero\Contracts\RoleModuleAccessInterface $hrmac */
            $hrmac = app(\Aero\Contracts\RoleModuleAccessInterface::class);
            $hrmac->syncRoleAccess($role, [
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
