<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Project Watcher Model
 *
 * Polymorphic model for users watching projects, tasks, or other entities.
 *
 * ARCHITECTURAL NOTE: Uses user_id only. Does NOT depend on HRM Employee model.
 */
class ProjectWatcher extends TenantModel
{
    protected $table = 'project_watchers';

    protected $fillable = [
        'watchable_type',
        'watchable_id',
        'user_id',
    ];

    /**
     * Get the watchable entity (project, task, risk, etc.).
     */
    public function watchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if a user is watching an entity.
     */
    public static function isWatching(Model $watchable, int $userId): bool
    {
        return static::query()
            ->where('watchable_type', $watchable->getMorphClass())
            ->where('watchable_id', $watchable->getKey())
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Add a watcher to an entity.
     */
    public static function watch(Model $watchable, int $userId): self
    {
        return static::firstOrCreate([
            'watchable_type' => $watchable->getMorphClass(),
            'watchable_id' => $watchable->getKey(),
            'user_id' => $userId,
        ]);
    }

    /**
     * Remove a watcher from an entity.
     */
    public static function unwatch(Model $watchable, int $userId): bool
    {
        return static::query()
            ->where('watchable_type', $watchable->getMorphClass())
            ->where('watchable_id', $watchable->getKey())
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Toggle watch status for a user on an entity.
     */
    public static function toggle(Model $watchable, int $userId): bool
    {
        if (static::isWatching($watchable, $userId)) {
            static::unwatch($watchable, $userId);

            return false;
        }

        static::watch($watchable, $userId);

        return true;
    }

    /**
     * Get all watcher user IDs for an entity.
     */
    public static function getWatcherIds(Model $watchable): array
    {
        return static::query()
            ->where('watchable_type', $watchable->getMorphClass())
            ->where('watchable_id', $watchable->getKey())
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Scope for specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific entity type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('watchable_type', $type);
    }
}
