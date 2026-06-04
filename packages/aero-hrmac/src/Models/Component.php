<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Component Model (Module Component)
 *
 * Represents a specific UI component or feature within a sub-module
 * that requires permission control (pages, sections, widgets, actions, APIs).
 * Connection-agnostic: uses current database context.
 */
class Component extends HrmacModel
{
    use HasFactory;

    protected $table = 'module_components';

    protected $fillable = [
        'sub_module_id',
        'module_id',
        'code',
        'name',
        'description',
        'type',
        'route',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Component types
    public const TYPE_PAGE = 'page';

    public const TYPE_SECTION = 'section';

    public const TYPE_WIDGET = 'widget';

    public const TYPE_ACTION = 'action';

    public const TYPE_API = 'api';

    public static function types(): array
    {
        return [
            self::TYPE_PAGE => 'Page',
            self::TYPE_SECTION => 'Section',
            self::TYPE_WIDGET => 'Widget',
            self::TYPE_ACTION => 'Action',
            self::TYPE_API => 'API Endpoint',
        ];
    }

    /**
     * Scope for active components.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the parent module.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the parent sub-module.
     */
    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class);
    }

    /**
     * Get actions for this component.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class, 'module_component_id');
    }

    /**
     * Get role access entries for this component.
     */
    public function roleAccess(): HasMany
    {
        return $this->hasMany(RoleModuleAccess::class, 'component_id');
    }

    /**
     * Get fully qualified code (module.submodule.component).
     */
    public function getFullCodeAttribute(): string
    {
        return $this->module->code.'.'.$this->subModule->code.'.'.$this->code;
    }
}
