<?php

declare(strict_types=1);

namespace Aero\Core\Traits;

use Aero\Core\Models\AuditLog;
use Aero\Core\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Taggable Trait
 *
 * Enables polymorphic tag assignment on any Eloquent model.
 *
 * Usage:
 *   class User extends Model
 *   {
 *       use Taggable;
 *   }
 *
 *   $user->tag(['VIP', 'Onboarding']);
 *   $user->untag(['Onboarding']);
 *   $user->syncTags(['VIP']);
 */
trait Taggable
{
    /**
     * Polymorphic relation to tags.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(
            Tag::class,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id'
        )
        ->withPivot('tenant_id')
        ->wherePivot('tenant_id', tenant('id'));
    }

    /**
     * Attach tags by name or slug.
     *
     * @param array<int,string>|string $names
     */
    public function tag(array|string $names): static
    {
        $names = is_array($names) ? $names : [$names];
        if (empty($names)) {
            return $this;
        }

        $tenantId = tenant('id');
        $ids = [];

        foreach ($names as $name) {
            $slug = \Illuminate\Support\Str::slug($name);
            $tag = Tag::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $slug],
                ['name' => $name, 'tenant_id' => $tenantId]
            );
            $ids[] = $tag->id;
        }

        $this->tags()->syncWithoutDetaching($ids);

        $this->logTaggableAudit('tags_attached', "Attached tags: " . implode(', ', $names), ['tag_ids' => $ids]);

        return $this;
    }

    /**
     * Detach tags by name or slug.
     *
     * @param array<int,string>|string $names
     */
    public function untag(array|string $names): static
    {
        $names = is_array($names) ? $names : [$names];
        if (empty($names)) {
            return $this;
        }

        $slugs = array_map(\Illuminate\Support\Str::slug(...), $names);
        $tenantId = tenant('id');

        $tagIds = Tag::forTenant($tenantId)
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->toArray();

        if (! empty($tagIds)) {
            $this->tags()->detach($tagIds);
            $this->logTaggableAudit('tags_detached', "Detached tags: " . implode(', ', $names), ['tag_ids' => $tagIds]);
        }

        return $this;
    }

    /**
     * Replace all tags on this model.
     *
     * @param array<int,string>|string $names
     */
    public function syncTags(array|string $names): static
    {
        $names = is_array($names) ? $names : [$names];
        $tenantId = tenant('id');
        $ids = [];

        foreach ($names as $name) {
            $slug = \Illuminate\Support\Str::slug($name);
            $tag = Tag::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $slug],
                ['name' => $name, 'tenant_id' => $tenantId]
            );
            $ids[] = $tag->id;
        }

        $this->tags()->sync($ids);

        $this->logTaggableAudit('tags_synced', "Synced tags: " . implode(', ', $names), ['tag_ids' => $ids]);

        return $this;
    }

    /**
     * Scope: records that have any of the given tag names.
     *
     * @param array<int,string> $names
     */
    public function scopeWithAnyTag(Builder $query, array $names): Builder
    {
        $slugs = array_map(\Illuminate\Support\Str::slug(...), $names);
        $tenantId = tenant('id');

        return $query->whereHas('tags', function (Builder $q) use ($slugs, $tenantId): void {
            $q->forTenant($tenantId)->whereIn('slug', $slugs);
        });
    }

    /**
     * Scope: records that have all of the given tag names.
     *
     * @param array<int,string> $names
     */
    public function scopeWithAllTags(Builder $query, array $names): Builder
    {
        $slugs = array_map(\Illuminate\Support\Str::slug(...), $names);
        $tenantId = tenant('id');

        foreach ($slugs as $slug) {
            $query->whereHas('tags', function (Builder $q) use ($slug, $tenantId): void {
                $q->forTenant($tenantId)->where('slug', $slug);
            });
        }

        return $query;
    }

    /**
     * Log a taggable-related audit entry.
     */
    private function logTaggableAudit(string $action, string $description, array $metadata = []): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name ?? 'System',
                'user_email' => auth()->user()?->email ?? '',
                'action' => $action,
                'auditable_type' => static::class,
                'auditable_id' => $this->getKey(),
                'description' => $description,
                'metadata' => array_merge($metadata, ['tenant_id' => tenant('id')]),
            ]);
        } catch (\Throwable $e) {
            // Silently fail if audit log table is not available
        }
    }
}
