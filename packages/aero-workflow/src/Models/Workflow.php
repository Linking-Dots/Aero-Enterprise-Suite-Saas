<?php

namespace Aero\Workflow\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'entity_type',
        'trigger_config',
        'is_active',
        'template_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('order');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
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

    public function scopeWithTemplate($query)
    {
        return $query->whereNotNull('template_id');
    }

    public function scopeCustom($query)
    {
        return $query->whereNull('template_id');
    }
}
