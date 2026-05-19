<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmExitInterview extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_exit_interviews';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_COMPLETED = 'completed';

    const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'employee_id', 'scheduled_for', 'interviewer_id', 'status',
        'responses', 'summary', 'eligible_for_rehire', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'completed_at' => 'datetime',
            'responses' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
