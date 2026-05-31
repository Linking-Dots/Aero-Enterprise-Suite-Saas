<?php

declare(strict_types=1);

namespace Aero\Core\Services;

use Aero\Core\Models\User;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data, User $actor): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

            if (! empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

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
            $role->update(['name' => $data['name']]);

            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions($data['permissions']);
            }

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'updated',
                null,
                'Role updated',
                null,
                null,
                ['role' => $role->name]
            );

            return $role->fresh(['permissions']);
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

    public function syncModulePermissions(Role $role, array $modulePermissions, User $actor): void
    {
        DB::transaction(function () use ($role, $modulePermissions) {
            $current = $role->permissions->pluck('name')->toArray();
            $role->syncPermissions($modulePermissions);

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'permissions_synced',
                null,
                'Role permissions synced',
                null,
                null,
                [
                    'role' => $role->name,
                    'granted' => array_values(array_diff($modulePermissions, $current)),
                    'revoked' => array_values(array_diff($current, $modulePermissions)),
                ]
            );
        });
    }
}
