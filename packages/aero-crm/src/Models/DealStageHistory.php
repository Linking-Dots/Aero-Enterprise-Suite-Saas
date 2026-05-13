<?php

namespace Aero\Crm\Models;

use Illuminate\Database\Eloquent\Model;

class DealStageHistory extends TenantModel
{
    public $timestamps = false;

    protected $table = 'deal_stage_history';

    protected $fillable = [
        'deal_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by',
        'time_in_stage_seconds',
        'deal_value_at_change',
        'probability_at_change',
        'changed_at',
    ];

    protected $casts = [
        'time_in_stage_seconds' => 'integer',
        'deal_value_at_change' => 'decimal:2',
        'probability_at_change' => 'integer',
        'changed_at' => 'datetime',
    ];

    /**
     * Get the deal
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Get the from stage
     */
    public function fromStage()
    {
        return $this->belongsTo(PipelineStage::class, 'from_stage_id');
    }

    /**
     * Get the to stage
     */
    public function toStage()
    {
        return $this->belongsTo(PipelineStage::class, 'to_stage_id');
    }

    /**
     * Get the user who changed the stage
     */
    public function changedBy()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'changed_by');
    }

    /**
     * Get human-readable time in stage
     */
    public function getTimeInStageHumanAttribute(): string
    {
        if (! $this->time_in_stage_seconds) {
            return 'N/A';
        }

        $seconds = $this->time_in_stage_seconds;

        if ($seconds < 60) {
            return $seconds.' seconds';
        }

        if ($seconds < 3600) {
            return round($seconds / 60).' minutes';
        }

        if ($seconds < 86400) {
            return round($seconds / 3600, 1).' hours';
        }

        return round($seconds / 86400, 1).' days';
    }
}
