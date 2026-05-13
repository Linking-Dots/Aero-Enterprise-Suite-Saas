<?php

namespace Aero\Crm\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pipeline extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_default',
        'is_active',
        'color',
        'icon',
        'type',
        'currency',
        'monthly_target',
        'quarterly_target',
        'yearly_target',
        'allowed_roles',
        'allowed_users',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'monthly_target' => 'decimal:2',
        'quarterly_target' => 'decimal:2',
        'yearly_target' => 'decimal:2',
        'allowed_roles' => 'array',
        'allowed_users' => 'array',
    ];

    /**
     * Pipeline types
     */
    const TYPE_SALES = 'sales';

    const TYPE_SUPPORT = 'support';

    const TYPE_RECRUITMENT = 'recruitment';

    const TYPE_CUSTOM = 'custom';

    /**
     * Get the stages in this pipeline
     */
    public function stages()
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }

    /**
     * Get active stages in this pipeline
     */
    public function activeStages()
    {
        return $this->hasMany(PipelineStage::class)
            ->where('is_active', true)
            ->orderBy('position');
    }

    /**
     * Get all deals in this pipeline (through stages)
     */
    public function deals()
    {
        return $this->hasManyThrough(Deal::class, PipelineStage::class);
    }

    /**
     * Get automations for this pipeline
     */
    public function automations()
    {
        return $this->hasMany(PipelineAutomation::class);
    }

    /**
     * Get custom field definitions for this pipeline
     */
    public function customFieldDefinitions()
    {
        return $this->hasMany(DealCustomFieldDefinition::class);
    }

    /**
     * Get the user who created the pipeline
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the pipeline
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active pipelines
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default pipeline
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default pipeline
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Calculate total pipeline value
     */
    public function getTotalValueAttribute()
    {
        return $this->deals()
            ->where('status', 'open')
            ->sum('value');
    }

    /**
     * Calculate weighted pipeline value
     */
    public function getWeightedValueAttribute()
    {
        return $this->deals()
            ->where('status', 'open')
            ->join('pipeline_stages', 'deals.pipeline_stage_id', '=', 'pipeline_stages.id')
            ->selectRaw('SUM(deals.value * pipeline_stages.probability / 100) as weighted_value')
            ->value('weighted_value') ?? 0;
    }

    /**
     * Get deals count by stage
     */
    public function getDealsCountByStage()
    {
        return $this->stages()
            ->withCount(['deals' => function ($query) {
                $query->where('status', 'open');
            }])
            ->get()
            ->pluck('deals_count', 'id');
    }
}
