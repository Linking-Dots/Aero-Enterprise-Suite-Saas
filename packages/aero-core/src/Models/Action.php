<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Action Model
 *
 * Represents an action within a component
 * (e.g., 'create', 'edit', 'delete', 'view')
 *
 * @property int $id
 * @property int $module_component_id
 * @property string $code Unique within component (e.g., 'create', 'edit', 'delete')
 * @property string $name Display name (e.g., 'Create Employee')
 * @property string|null $description
 * @property bool $is_active
 */
class Action extends TenantModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'module_component_actions';

    protected $fillable = [
        'module_component_id',
        'code',
        'name',
        'description',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Parent component relationship.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'module_component_id');
    }

    /**
     * Scope to active actions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
