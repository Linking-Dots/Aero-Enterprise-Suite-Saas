<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\LandlordRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LandlordRoleService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(): Collection
    {
        return LandlordRole::withCount('users')->orderBy('name')->get();
    }

    public function create(array $data): LandlordRole
    {
        return DB::transaction(function () use ($data) {
            $role = LandlordRole::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'permissions' => $data['permissions'] ?? [],
                'is_system' => false,
            ]);

            $this->audit->log(
                event: 'platform.roles.created',
                action: 'manage',
                subject: $role,
                description: "Landlord role created: {$role->name}",
            );

            return $role;
        });
    }

    public function update(LandlordRole $role, array $data): LandlordRole
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(collect($data)->only(['name', 'description', 'permissions'])->toArray());

            $this->audit->log(
                event: 'platform.roles.updated',
                action: 'manage',
                subject: $role,
                description: "Landlord role updated: {$role->name}",
            );

            return $role->refresh();
        });
    }

    public function delete(LandlordRole $role): void
    {
        if ($role->is_system) {
            abort(422, 'System roles cannot be deleted.');
        }

        DB::transaction(function () use ($role) {
            $name = $role->name;
            $role->delete();

            $this->audit->log(
                event: 'platform.roles.deleted',
                action: 'manage',
                subject: $role,
                description: "Landlord role deleted: {$name}",
            );
        });
    }

    public function clone(LandlordRole $role, string $newName): LandlordRole
    {
        return DB::transaction(function () use ($role, $newName) {
            $copy = LandlordRole::create([
                'name' => $newName,
                'description' => $role->description.' (cloned)',
                'permissions' => $role->permissions,
                'is_system' => false,
            ]);

            $this->audit->log(
                event: 'platform.roles.cloned',
                action: 'manage',
                subject: $copy,
                description: "Role {$role->name} cloned to {$copy->name}",
            );

            return $copy;
        });
    }

    public function assignPermissions(LandlordRole $role, array $permissions): LandlordRole
    {
        return DB::transaction(function () use ($role, $permissions) {
            $role->update(['permissions' => array_values(array_unique($permissions))]);

            $this->audit->log(
                event: 'platform.roles.permissions_updated',
                action: 'manage',
                subject: $role,
                description: "Permissions updated for role {$role->name}",
            );

            return $role->refresh();
        });
    }
}
