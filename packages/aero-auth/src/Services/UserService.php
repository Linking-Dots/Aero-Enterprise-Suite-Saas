<?php

declare(strict_types=1);

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Aero\Contracts\AuditServiceInterface;
use Aero\HRMAC\Services\RoleService;
use Aero\Kernel\Audit\AuditEventType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Unified user-management service for BOTH contexts (tenant + landlord).
 *
 * Merged from the former Aero\Core\Services\UserService (tenant) and
 * Aero\Platform\Services\LandlordUserService (landlord) — auth owns the one
 * identity capability; platform/core are pure consumers.
 *
 * Active/inactive is managed via Laravel SoftDeletes (Boss ruling 2026-06-14):
 *   - active   = NOT soft-deleted
 *   - inactive = soft-deleted (deleted_at set) — toggleStatus() soft-deletes/restores
 *   - delete() / bulkDelete() = forceDelete() (PERMANENT, irreversible)
 * The vestigial active/is_active boolean columns are no longer the source of truth.
 *
 * Roles are assigned by ID via the (allowed) auth->hrmac RoleService edge.
 * Audit is unified to the generic data.* taxonomy (kernel AuditEventType) via
 * the AuditServiceInterface contract.
 */
class UserService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly RoleService $roles,
    ) {}

    /**
     * @param  array<string, mixed>  $filters  search, role (id|name), status (active|inactive|all)
     */
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        // active = not trashed (default query). 'inactive' = onlyTrashed.
        // 'all'/unspecified = withTrashed (show both, the pre-merge default).
        $status = ($filters['status'] ?? null) ?: 'all';

        return User::query()
            ->when($status === 'inactive', fn ($q) => $q->onlyTrashed())
            ->when($status === 'all', fn ($q) => $q->withTrashed())
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->when($filters['role'] ?? null, fn ($q, $r) => $q->whereHas('roles', fn ($q2) => is_numeric($r)
                ? $q2->where('roles.id', $r)
                : $q2->where('name', $r)))
            ->with('roles:id,name')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data  name, email, password, [user_name], [timezone], [role_ids]
     */
    public function create(array $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'user_name' => $data['user_name'] ?? Str::slug($data['name'], '_').'_'.Str::random(4),
                'email'     => $data['email'],
                'password'  => Hash::make($data['password'] ?? Str::random(16)),
                'timezone'  => $data['timezone'] ?? 'UTC',
            ]);

            if (! empty($data['role_ids'])) {
                $this->roles->assignToUser($user, $data['role_ids']);
            }

            $this->audit->log(
                AuditEventType::RECORD_CREATED->value,
                'created',
                $user,
                'User created',
                null,
                null,
                ['email' => $user->email],
            );

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data  [name], [email], [timezone], [password], [role_ids]
     */
    public function update(User $user, array $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = array_filter([
                'name'     => $data['name'] ?? null,
                'email'    => $data['email'] ?? null,
                'timezone' => $data['timezone'] ?? null,
            ], fn ($v) => $v !== null);

            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            if (! empty($payload)) {
                $user->update($payload);
            }

            if (array_key_exists('role_ids', $data)) {
                $this->roles->assignToUser($user, $data['role_ids'] ?? []);
            }

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'updated',
                $user,
                'User updated',
            );

            return $user->refresh();
        });
    }

    /**
     * Permanently remove the user (admin "delete"). Irreversible — see class doc.
     */
    public function delete(User $user, ?User $actor = null): void
    {
        DB::transaction(function () use ($user) {
            $email = $user->email;

            $this->audit->log(
                AuditEventType::RECORD_DELETED->value,
                'deleted',
                $user,
                'User deleted',
                null,
                null,
                ['email' => $email],
            );

            $user->forceDelete();
        });
    }

    /**
     * Toggle active state via soft-delete: active -> soft-deleted (deactivate),
     * trashed -> restore (reactivate).
     */
    public function toggleStatus(User $user, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user) {
            $wasActive = ! $user->trashed();

            if ($wasActive) {
                $user->delete();   // soft delete = deactivate
            } else {
                $user->restore();  // reactivate
            }

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                $wasActive ? 'deactivated' : 'activated',
                $user,
                'User status toggled',
            );

            return $user;
        });
    }

    /**
     * Permanently remove the given users (excluding the actor). Irreversible.
     *
     * @param  array<int, int|string>  $ids
     */
    public function bulkDelete(array $ids, ?User $actor = null): int
    {
        return DB::transaction(function () use ($ids, $actor) {
            $users = User::withTrashed()
                ->whereIn('id', $ids)
                ->when($actor, fn ($q) => $q->where('id', '!=', $actor->id))
                ->get();

            foreach ($users as $u) {
                $email = $u->email;
                $this->audit->log(
                    AuditEventType::RECORD_DELETED->value,
                    'deleted',
                    $u,
                    'User deleted (bulk)',
                    null,
                    null,
                    ['email' => $email],
                );
                $u->forceDelete();
            }

            return $users->count();
        });
    }

    /**
     * @param  array<int, int|string>  $ids
     * @param  array<int, int|string>  $roleIds
     */
    public function bulkAssignRoles(array $ids, array $roleIds, ?User $actor = null): int
    {
        return DB::transaction(function () use ($ids, $roleIds) {
            $users = User::whereIn('id', $ids)->get();

            foreach ($users as $u) {
                $this->roles->assignToUser($u, $roleIds);
            }

            return $users->count();
        });
    }
}
