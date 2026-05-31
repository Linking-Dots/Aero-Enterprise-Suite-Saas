<?php

declare(strict_types=1);

namespace Aero\Core\Services;

use Aero\Core\Models\Announcement;
use Aero\Core\Models\User;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AnnouncementService
{
    public function __construct(private readonly AuditService $audit) {}

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Announcement::with('author')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['audience'] ?? null, fn ($q, $s) => $q->where('audience', $s))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, User $actor): Announcement
    {
        return DB::transaction(function () use ($data, $actor) {
            $ann = Announcement::create(array_merge($data, ['created_by' => $actor->id]));

            $this->audit->log(
                AuditEventType::RECORD_CREATED->value,
                'created',
                $ann,
                'Announcement created',
                null,
                null,
                ['title' => $ann->title]
            );

            return $ann;
        });
    }

    public function update(Announcement $ann, array $data, User $actor): Announcement
    {
        return DB::transaction(function () use ($ann, $data) {
            $ann->update($data);

            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'updated',
                $ann,
                'Announcement updated'
            );

            return $ann->fresh();
        });
    }

    public function publish(Announcement $ann, User $actor): Announcement
    {
        return DB::transaction(function () use ($ann) {
            $ann->update(['status' => 'published', 'published_at' => now()]);
            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'published',
                $ann,
                'Announcement published'
            );

            return $ann->fresh();
        });
    }

    public function archive(Announcement $ann, User $actor): Announcement
    {
        return DB::transaction(function () use ($ann) {
            $ann->update(['status' => 'archived']);
            $this->audit->log(
                AuditEventType::RECORD_UPDATED->value,
                'archived',
                $ann,
                'Announcement archived'
            );

            return $ann->fresh();
        });
    }

    public function delete(Announcement $ann, User $actor): void
    {
        DB::transaction(function () use ($ann) {
            $this->audit->log(
                AuditEventType::RECORD_DELETED->value,
                'deleted',
                $ann,
                'Announcement deleted',
                null,
                null,
                ['title' => $ann->title]
            );
            $ann->delete();
        });
    }
}
