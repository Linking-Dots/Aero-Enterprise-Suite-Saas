<?php

namespace Aero\Integration\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowExecution extends TenantModel
{
    use HasFactory;

    protected $table = 'integration_workflow_executions';

    protected $fillable = [
        'workflow_definition_id', 'execution_id', 'trigger_data', 'status',
        'started_at', 'completed_at', 'duration_seconds', 'step_results',
        'error_message', 'triggered_by',
    ];

    protected $casts = [
        'workflow_definition_id' => 'integer',
        'trigger_data' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'step_results' => 'json',
        'triggered_by' => 'integer',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_RUNNING = 'running';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_TIMEOUT = 'timeout';

    public function workflowDefinition()
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function steps()
    {
        return $this->hasMany(WorkflowExecutionStep::class);
    }

    public function isRunning()
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isCompleted()
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }

        return $this->duration_seconds;
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
