<?php

namespace Aero\Crm\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PipelineStage extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pipeline_id',
        'name',
        'code',
        'description',
        'position',
        'probability',
        'stage_type',
        'rotting_days',
        'color',
        'icon',
        'max_deals',
        'sla_hours',
        'auto_email_on_enter',
        'auto_task_on_enter',
        'automation_config',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'position' => 'integer',
        'probability' => 'integer',
        'rotting_days' => 'integer',
        'max_deals' => 'integer',
        'sla_hours' => 'integer',
        'auto_email_on_enter' => 'boolean',
        'auto_task_on_enter' => 'boolean',
        'automation_config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Stage types
     */
    const TYPE_OPEN = 'open';

    const TYPE_WON = 'won';

    const TYPE_LOST = 'lost';

    const TYPE_ROTTING = 'rotting';

    /**
     * Get the pipeline this stage belongs to
     */
    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    /**
     * Get the deals in this stage
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'pipeline_stage_id')->orderBy('position');
    }

    /**
     * Get active deals in this stage
     */
    public function activeDeals()
    {
        return $this->hasMany(Deal::class, 'pipeline_stage_id')
            ->where('status', 'open')
            ->orderBy('position');
    }

    /**
     * Get the user who created the stage
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active stages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Scope for open stages
     */
    public function scopeOpen($query)
    {
        return $query->where('stage_type', self::TYPE_OPEN);
    }

    /**
     * Scope for closed stages (won or lost)
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('stage_type', [self::TYPE_WON, self::TYPE_LOST]);
    }

    /**
     * Check if this is a won stage
     */
    public function isWon(): bool
    {
        return $this->stage_type === self::TYPE_WON;
    }

    /**
     * Check if this is a lost stage
     */
    public function isLost(): bool
    {
        return $this->stage_type === self::TYPE_LOST;
    }

    /**
     * Check if this is a closed stage
     */
    public function isClosed(): bool
    {
        return in_array($this->stage_type, [self::TYPE_WON, self::TYPE_LOST]);
    }

    /**
     * Get total value of deals in this stage
     */
    public function getTotalValueAttribute()
    {
        return $this->deals()->where('status', 'open')->sum('value');
    }

    /**
     * Get count of deals in this stage
     */
    public function getDealsCountAttribute()
    {
        return $this->deals()->where('status', 'open')->count();
    }

    /**
     * Check if WIP limit is reached
     */
    public function isWipLimitReached(): bool
    {
        if (! $this->max_deals) {
            return false;
        }

        return $this->deals()->where('status', 'open')->count() >= $this->max_deals;
    }

    /**
     * Get the next stage in the pipeline
     */
    public function getNextStage()
    {
        return static::where('pipeline_id', $this->pipeline_id)
            ->where('position', '>', $this->position)
            ->where('is_active', true)
            ->orderBy('position')
            ->first();
    }

    /**
     * Get the previous stage in the pipeline
     */
    public function getPreviousStage()
    {
        return static::where('pipeline_id', $this->pipeline_id)
            ->where('position', '<', $this->position)
            ->where('is_active', true)
            ->orderBy('position', 'desc')
            ->first();
    }
}
