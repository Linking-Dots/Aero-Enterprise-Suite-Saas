<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class PipelineAutomation extends TenantModel
{
    protected $fillable = [
        'pipeline_id',
        'stage_id',
        'name',
        'description',
        'trigger_type',
        'conditions',
        'actions',
        'is_active',
        'execution_count',
        'last_executed_at',
        'created_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'execution_count' => 'integer',
        'last_executed_at' => 'datetime',
    ];

    /**
     * Trigger types
     */
    const TRIGGER_DEAL_CREATED = 'deal_created';

    const TRIGGER_STAGE_CHANGED = 'deal_stage_changed';

    const TRIGGER_DEAL_WON = 'deal_won';

    const TRIGGER_DEAL_LOST = 'deal_lost';

    const TRIGGER_VALUE_CHANGED = 'deal_value_changed';

    const TRIGGER_DEAL_ASSIGNED = 'deal_assigned';

    const TRIGGER_DEAL_ROTTING = 'deal_rotting';

    const TRIGGER_SCHEDULED = 'scheduled';

    /**
     * Get the pipeline
     */
    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    /**
     * Get the stage (if stage-specific)
     */
    public function stage()
    {
        return $this->belongsTo(PipelineStage::class);
    }

    /**
     * Get the user who created the automation
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active automations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by trigger type
     */
    public function scopeTrigger($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Increment execution count
     */
    public function recordExecution(): void
    {
        $this->increment('execution_count');
        $this->update(['last_executed_at' => now()]);
    }
}
