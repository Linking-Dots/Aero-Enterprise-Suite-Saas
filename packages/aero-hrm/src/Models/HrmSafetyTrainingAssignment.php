<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmSafetyTrainingAssignment extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_safety_training_assignments';

    const STATUS_ASSIGNED = 'assigned';

    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'training_id',
        'employee_id',
        'assigned_by',
        'due_date',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(HrmSafetyTraining::class, 'training_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
