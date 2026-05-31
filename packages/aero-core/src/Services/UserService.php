<?php

declare(strict_types=1);

namespace Aero\Core\Services;

use Aero\Core\Models\User;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(private readonly AuditService $audit) {}

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->when($filters['role'] ?? null, fn ($q, $r) => $q->whereHas('roles', fn ($q2) => $q2->where('name', $r)))
            ->when(isset($filters['status']), fn ($q) => $q->where('is_active', $filters['status'] === 'active'))
            ->with(['roles'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::create([
                'name'      => $data['name'],
                'user_name' => $data['user_name'] ?? Str::slug($data['name'], '_') . '_' . Str::random(4),
                'email'     => $data['email'],
                'password'  => Hash::make($data['password'] ?? Str::random(16)),
                'is_active' => true,
            ]);

            if (! empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            $this->audit->log(
                AuditEventType::RECORD_CREATED->value,
                'created',
                $user,
                'User created',
                null,
                null,
                ['email' => $user->email]
            );

            return $user;
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $user->update(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
            ], fn ($v) => $v !== null));

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles']);
            }

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'updated',
                $user,
                'User updated'
            );

            return $user->fresh();
        });
    }

    public function delete(User $user, User $actor): void
    {
        DB::transaction(function () use ($user) {
            $this->audit->log(
                AuditEventType::RECORD_DELETED->value,
                'deleted',
                $user,
                'User deleted',
                null,
                null,
                ['email' => $user->email]
            );
            $user->delete();
        });
    }

    public function toggleStatus(User $user, User $actor): User
    {
        return DB::transaction(function () use ($user, $actor) {
            $newStatus = ! $user->is_active;
            $action    = $newStatus ? 'activated' : 'deactivated';
            $user->update(['is_active' => $newStatus]);
            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                $action,
                $user,
                'User status toggled'
            );
            return $user->fresh();
        });
    }

    public function bulkDelete(array $ids, User $actor): int
    {
        return DB::transaction(function () use ($ids, $actor) {
            $users = User::whereIn('id', $ids)->where('id', '!=', $actor->id)->get();

            foreach ($users as $u) {
                $this->audit->log(
                    AuditEventType::RECORD_DELETED->value,
                    'deleted',
                    $u,
                    'User deleted (bulk)',
                    null,
                    null,
                    ['email' => $u->email]
                );
                $u->delete();
            }

            return $users->count();
        });
    }

    public function bulkAssignRoles(array $ids, array $roles, User $actor): int
    {
        return DB::transaction(function () use ($ids, $roles, $actor) {
            $users = User::whereIn('id', $ids)->get();

            foreach ($users as $u) {
                $u->syncRoles($roles);
                $this->audit->log(
                    AuditEventType::RECORD_UPDATED->value,
                    'roles_assigned',
                    $u,
                    'Roles bulk-assigned',
                    null,
                    null,
                    ['roles' => $roles]
                );
            }

            return $users->count();
        });
    }
}
