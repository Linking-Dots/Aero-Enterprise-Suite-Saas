<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Module Component Action Model
 *
 * Represents granular actions that can be performed on a component.
 * Examples: view, create, update, delete, export, import, approve, etc.
 */
class ModuleComponentAction extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'module_component_id',
        'code',
        'name',
        'description',
    ];

    /**
     * Get the component this action belongs to.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(ModuleComponent::class, 'module_component_id');
    }

    /**
     * Get permission requirements for this action.
     */
    public function permissionRequirements(): HasMany
    {
        return $this->hasMany(ModulePermission::class, 'module_component_action_id');
    }
}
