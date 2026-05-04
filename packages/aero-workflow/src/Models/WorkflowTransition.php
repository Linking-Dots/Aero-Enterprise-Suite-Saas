<?php

namespace Aero\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransition extends Model
{
    protected $fillable = [
        'instance_id',
        'from_step_id',
        'to_step_id',
        'action',
        'comment',
        'performed_by',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'from_step_id');
    }

    public function toStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'to_step_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'performed_by');
    }

    public function scopeApprove($query)
    {
        return $query->where('action', 'approve');
    }

    public function scopeReject($query)
    {
        return $query->where('action', 'reject');
    }

    public function scopeEscalate($query)
    {
        return $query->where('action', 'escalate');
    }

    public function scopeSkip($query)
    {
        return $query->where('action', 'skip');
    }
}
