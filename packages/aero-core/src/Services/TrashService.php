<?php

namespace Aero\Core\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Trash Service
 *
 * Core business logic for managing trashed items across entities.
 */
class TrashService
{
    /**
     * Get entity model classes that support soft deletes.
     */
    public function getEntityModels(): array
    {
        return [
            'users' => \Aero\Core\Models\User::class,
            'comments' => \Aero\Core\Models\Comment::class,
            'tags' => \Aero\Core\Models\Tag::class,
            'activities' => \Aero\Core\Models\Activity::class,
            'data_exports' => \Aero\Core\Models\DataExport::class,
            'retention_policies' => \Aero\Core\Models\RetentionPolicy::class,
        ];
    }

    /**
     * Get entity display names.
     */
    public function getEntityNames(): array
    {
        return [
            'users' => 'Users',
            'comments' => 'Comments',
            'tags' => 'Tags',
            'activities' => 'Activities',
            'data_exports' => 'Data Exports',
            'retention_policies' => 'Retention Policies',
        ];
    }

    /**
     * Get trashed items for a specific entity.
     */
    public function getTrashedItems(string $entity, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $modelClass = $this->getEntityModels()[$entity] ?? null;

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entity}");
        }

        $query = $modelClass::onlyTrashed();

        if (isset($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('deleted_at', 'desc')->get();
    }

    /**
     * Get trashed counts per entity.
     */
    public function getEntityCounts(): array
    {
        $counts = [];

        foreach ($this->getEntityModels() as $entity => $modelClass) {
            $counts[$entity] = $modelClass::onlyTrashed()->count();
        }

        return $counts;
    }

    /**
     * Restore a trashed item.
     */
    public function restoreItem(string $entity, int $id): Model
    {
        $modelClass = $this->getEntityModels()[$entity] ?? null;

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entity}");
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

        return $item;
    }

    /**
     * Bulk restore trashed items.
     */
    public function bulkRestore(string $entity, array $ids): int
    {
        $modelClass = $this->getEntityModels()[$entity] ?? null;

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entity}");
        }

        return $modelClass::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Permanently delete a trashed item.
     */
    public function forceDeleteItem(string $entity, int $id): void
    {
        $modelClass = $this->getEntityModels()[$entity] ?? null;

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entity}");
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->forceDelete();
    }

    /**
     * Bulk permanently delete trashed items.
     */
    public function bulkForceDelete(string $entity, array $ids): int
    {
        $modelClass = $this->getEntityModels()[$entity] ?? null;

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entity}");
        }

        return $modelClass::onlyTrashed()->whereIn('id', $ids)->forceDelete();
    }

    /**
     * Empty all trashed items for an entity.
     */
    public function emptyTrash(string $entity): int
    {
        $modelClass = $this->getEntityModels()[$entity] ?? null;

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entity}");
        }

        return $modelClass::onlyTrashed()->forceDelete();
    }

    /**
     * Empty all trashed items across all entities.
     */
    public function emptyAllTrash(): array
    {
        $results = [];

        foreach ($this->getEntityModels() as $entity => $modelClass) {
            $results[$entity] = $modelClass::onlyTrashed()->forceDelete();
        }

        return $results;
    }
}
