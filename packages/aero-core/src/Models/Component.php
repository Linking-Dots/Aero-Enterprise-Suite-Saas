<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Component Model
 *
 * Represents a component under a sub-module
 * (e.g., 'employee_list' under 'employee_management')
 *
 * @property int $id
 * @property int $sub_module_id
 * @property string $code Unique within sub-module (e.g., 'employee_list')
 * @property string $name
 * @property string|null $description
 * @property string|null $route_name
 * @property int $priority
 * @property bool $is_active
 */
class Component extends TenantModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'module_components';

    protected $fillable = [
        'sub_module_id',
        'code',
        'name',
        'description',
        'route_name',
        'priority',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Parent sub-module relationship.
     */
    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class);
    }

    /**
     * Actions within this component.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    /**
     * Active actions only.
     */
    public function activeActions()
    {
        return $this->actions()->where('is_active', true);
    }

    /**
     * Scope to active components.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by priority order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority');
    }
}
