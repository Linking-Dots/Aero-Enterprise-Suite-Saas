<?php

declare(strict_types=1);

namespace Aero\Platform\Services\AdvancedBilling;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\PlatformAddon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AddonService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * @return LengthAwarePaginator<PlatformAddon>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return PlatformAddon::query()
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('code', 'like', '%'.$filters['search'].'%')
            )
            ->latest('created_at')
            ->paginate(20);
    }

    public function create(array $data, int $actorId): PlatformAddon
    {
        return DB::transaction(function () use ($data, $actorId) {
            $data['created_by'] = $actorId;

            /** @var PlatformAddon $addon */
            $addon = PlatformAddon::create($data);

            $this->audit->log(
                AuditEventType::ADDON_CREATED->value,
                'addon.create',
                $addon,
                "Add-on [{$addon->code}] created."
            );

            return $addon;
        });
    }

    public function update(PlatformAddon $addon, array $data, int $actorId): PlatformAddon
    {
        return DB::transaction(function () use ($addon, $data) {
            $addon->update($data);

            $this->audit->log(
                AuditEventType::ADDON_UPDATED->value,
                'addon.update',
                $addon,
                "Add-on [{$addon->code}] updated."
            );

            return $addon->refresh();
        });
    }

    public function archive(PlatformAddon $addon, int $actorId): PlatformAddon
    {
        return DB::transaction(function () use ($addon) {
            $addon->update(['status' => PlatformAddon::STATUS_ARCHIVED]);

            $this->audit->log(
                AuditEventType::ADDON_ARCHIVED->value,
                'addon.archive',
                $addon,
                "Add-on [{$addon->code}] archived."
            );

            return $addon->refresh();
        });
    }
}
