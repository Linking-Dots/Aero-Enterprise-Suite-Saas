<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRMAC\Services\RoleService;
use Aero\Platform\Models\LandlordUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordUserService
{
    public function __construct(
        private AuditServiceInterface $audit,
        private RoleService $roles,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return LandlordUser::query()
            ->with('roles:id,name')
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%");
            }))
            ->when(isset($filters['active']), fn ($q) => $q->where('active', (bool) $filters['active']))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data): LandlordUser
    {
        return DB::transaction(function () use ($data) {
            $user = LandlordUser::create([
                'user_name' => $data['user_name'] ?? str($data['email'])->before('@'),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'active' => $data['active'] ?? true,
                'timezone' => $data['timezone'] ?? 'UTC',
            ]);

            if (! empty($data['role_ids'])) {
                $this->roles->assignToUser($user, $data['role_ids']);
            }

            $this->audit->log(
                event: AuditEventType::LANDLORD_USER_CREATED->value,
                action: 'create',
                subject: $user,
                description: "Landlord user created: {$user->email}",
            );

            return $user;
        });
    }

    public function update(LandlordUser $user, array $data): LandlordUser
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = collect($data)->only(['name', 'email', 'active', 'timezone'])->toArray();

            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->update($payload);

            if (array_key_exists('role_ids', $data)) {
                $this->roles->assignToUser($user, $data['role_ids'] ?? []);
            }

            $this->audit->log(
                event: AuditEventType::LANDLORD_USER_UPDATED->value,
                action: 'edit',
                subject: $user,
                description: "Landlord user updated: {$user->email}",
            );

            return $user->refresh();
        });
    }

    public function delete(LandlordUser $user): void
    {
        DB::transaction(function () use ($user) {
            $email = $user->email;
            $user->delete();

            $this->audit->log(
                event: AuditEventType::LANDLORD_USER_DELETED->value,
                action: 'delete',
                subject: $user,
                description: "Landlord user deleted: {$email}",
            );
        });
    }

    public function toggleStatus(LandlordUser $user): LandlordUser
    {
        return DB::transaction(function () use ($user) {
            $user->update(['active' => ! $user->active]);

            $this->audit->log(
                event: AuditEventType::LANDLORD_USER_STATUS_TOGGLED->value,
                action: 'edit',
                subject: $user,
                description: 'Landlord user status set to '.($user->active ? 'active' : 'inactive'),
            );

            return $user->refresh();
        });
    }
}
