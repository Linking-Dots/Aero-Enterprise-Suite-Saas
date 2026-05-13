<?php

declare(strict_types=1);

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SavedView Model
 *
 * User and shared saved views across modules for filter configurations.
 *
 * @property int $id
 * @property int $user_id
 * @property string $module_code
 * @property string $route
 * @property string $name
 * @property string|null $description
 * @property array $filters
 * @property array|null $sort
 * @property array|null $columns
 * @property bool $is_default
 * @property bool $is_shared
 * @property array|null $shared_with
 * @property bool $is_system
 */
class SavedView extends TenantModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'module_code',
        'route',
        'name',
        'description',
        'filters',
        'sort',
        'columns',
        'is_default',
        'is_shared',
        'shared_with',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'filters' => 'array',
        'sort' => 'array',
        'columns' => 'array',
        'shared_with' => 'array',
        'is_default' => 'boolean',
        'is_shared' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SavedView $view): void {
            // Ensure only one default view per user+route
            if ($view->is_default) {
                static::where('user_id', $view->user_id)
                    ->where('route', $view->route)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function (SavedView $view): void {
            // Handle default view changes
            if ($view->is_default && $view->isDirty('is_default')) {
                static::where('user_id', $view->user_id)
                    ->where('route', $view->route)
                    ->where('id', '!=', $view->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Relationship: User who created this view.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Scope: filter by current user.
     */
    public function scopeForUser(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= auth()->id();

        return $query->where('user_id', $userId);
    }

    /**
     * Scope: filter by module code.
     */
    public function scopeForModule(Builder $query, string $moduleCode): Builder
    {
        return $query->where('module_code', $moduleCode);
    }

    /**
     * Scope: filter by route.
     */
    public function scopeForRoute(Builder $query, string $route): Builder
    {
        return $query->where('route', $route);
    }

    /**
     * Scope: shared views (either system-wide or user-shared).
     */
    public function scopeShared(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('is_system', true)
                ->orWhere('is_shared', true);
        });
    }

    /**
     * Scope: system-wide views.
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: default views.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: views shared with current user or their role.
     */
    public function scopeSharedWithUser(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= auth()->id();

        return $query->where('is_shared', true)
            ->whereJsonContains('shared_with', (string) $userId);
    }

    /**
     * Check if this view is accessible by the current user.
     */
    public function isAccessibleBy(?int $userId = null): bool
    {
        $userId ??= auth()->id();

        // Owner can always access
        if ($this->user_id === $userId) {
            return true;
        }

        // System views are accessible to all
        if ($this->is_system) {
            return true;
        }

        // Shared views check
        if ($this->is_shared && $this->shared_with) {
            return in_array((string) $userId, $this->shared_with, true);
        }

        return false;
    }
}
