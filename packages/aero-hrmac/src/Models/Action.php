<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Action Model (Module Component Action)
 *
 * Represents granular actions that can be performed on a component.
 * Examples: view, create, update, delete, export, import, approve, etc.
 * Connection-agnostic: uses current database context.
 */
class Action extends HrmacModel
{
    use HasFactory;

    protected $table = 'module_component_actions';

    protected $fillable = [
        'module_component_id',
        'code',
        'name',
        'description',
    ];

    protected $casts = [];

    /**
     * Get the parent component.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'module_component_id');
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
