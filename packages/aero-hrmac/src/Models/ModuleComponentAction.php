<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ModuleComponentAction Model
 *
 * Represents granular actions that can be performed on a component.
 * Examples: view, create, update, delete, export, import, approve, etc.
 * Connection-agnostic: uses current database context.
 */
class ModuleComponentAction extends HrmacModel
{
    use HasFactory;

    protected $table = 'module_component_actions';

    protected $fillable = [
        'module_component_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent component.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(ModuleComponent::class, 'module_component_id');
    }

    /**
     * Get role access entries for this action.
     */
    public function roleAccess(): HasMany
    {
        return $this->hasMany(RoleModuleAccess::class);
    }

    /**
     * Get fully qualified code (module.submodule.component.action).
     */
    public function getFullCodeAttribute(): string
    {
        $component = $this->component;

        return $component->module->code.'.'
            .$component->subModule->code.'.'
            .$component->code.'.'
            .$this->code;
    }
}
