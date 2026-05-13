<?php

namespace Aero\Platform\Models;

use Aero\Platform\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Action Model
 *
 * Represents an action within a component
 * (e.g., 'create', 'edit', 'delete', 'view')
 *
 * @property int $id
 * @property int $component_id
 * @property string $code Unique within component (e.g., 'create', 'edit', 'delete')
 * @property string $name Display name (e.g., 'Create Employee')
 * @property string|null $description
 * @property bool $is_active
 */
class Action extends CentralModel
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'component_id',
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
        return $this->belongsTo(Component::class);
    }

    /**
     * Scope to active actions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
