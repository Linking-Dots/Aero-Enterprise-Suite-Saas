<?php

namespace Aero\Integration\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowDefinition extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integration_workflow_definitions';

    protected $fillable = [
        'name', 'description', 'trigger_type', 'trigger_conditions', 'workflow_steps',
        'is_active', 'version', 'category', 'priority', 'timeout_minutes',
        'retry_attempts', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'trigger_conditions' => 'json',
        'workflow_steps' => 'json',
        'is_active' => 'boolean',
        'version' => 'integer',
        'timeout_minutes' => 'integer',
        'retry_attempts' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const TRIGGER_MANUAL = 'manual';

    const TRIGGER_SCHEDULED = 'scheduled';

    const TRIGGER_EVENT = 'event';

    const TRIGGER_WEBHOOK = 'webhook';

    const TRIGGER_API = 'api';

    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_CRITICAL = 'critical';

    const CATEGORY_DATA_SYNC = 'data_sync';

    const CATEGORY_NOTIFICATION = 'notification';

    const CATEGORY_APPROVAL = 'approval';

    const CATEGORY_AUTOMATION = 'automation';

    const CATEGORY_INTEGRATION = 'integration';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function executions()
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    public function triggers()
    {
        return $this->hasMany(WorkflowTrigger::class);
    }

    public function isApproved()
    {
        return $this->approved_at !== null;
    }

    public function getExecutionStatsAttribute()
    {
        $total = $this->executions()->count();
        $successful = $this->executions()->where('status', WorkflowExecution::STATUS_COMPLETED)->count();
        $failed = $this->executions()->where('status', WorkflowExecution::STATUS_FAILED)->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }
}
