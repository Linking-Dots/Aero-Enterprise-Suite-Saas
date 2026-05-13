<?php

namespace Aero\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTemplate extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'entity_type',
        'steps_config',
        'is_active',
        'is_system',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'steps_config' => 'array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }
}
