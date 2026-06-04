<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SubModule Model
 *
 * Represents a functional area within a module (e.g., Employees within HRM)
 * Connection-agnostic: uses current database context.
 */
class SubModule extends HrmacModel
{
    use HasFactory;

    protected $table = 'sub_modules';

    protected $fillable = [
        'module_id',
        'parent_id',
        'code',
        'name',
        'description',
        'icon',
        'route',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Scope for active sub-modules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by priority then name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('name');
    }

    /**
     * Get the parent module.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get components for this sub-module.
     */
    public function components(): HasMany
    {
        return $this->hasMany(Component::class)->orderBy('priority');
    }

    /**
     * Parent sub-module (nested sub-modules). Null for top-level.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child sub-modules (nested tree).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('priority');
    }

    /**
     * Ancestor IDs up the parent chain (nearest first), for access cascade.
     *
     * @return array<int>
     */
    public function ancestorIds(): array
    {
        $ids = [];
        $node = $this->parent;
        $guard = 0;
        while ($node && $guard++ < 20) {
            $ids[] = $node->id;
            $node = $node->parent;
        }

        return $ids;
    }

    /**
     * Get role access entries for this sub-module.
     */
    public function roleAccess(): HasMany
    {
        return $this->hasMany(RoleModuleAccess::class);
    }

    /**
     * Find sub-module by module and code.
     */
    public static function findByCode(int $moduleId, string $code): ?self
    {
        return static::where('module_id', $moduleId)
            ->where('code', $code)
            ->first();
    }

    /**
     * Get fully qualified code (module.submodule).
     */
    public function getFullCodeAttribute(): string
    {
        return $this->module->code.'.'.$this->code;
    }
}
