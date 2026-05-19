<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmSafetyInspection extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_safety_inspections';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'reference',
        'title',
        'scheduled_date',
        'conducted_date',
        'inspector_id',
        'department_id',
        'location',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'conducted_date' => 'date',
        ];
    }

    public function findings(): HasMany
    {
        return $this->hasMany(HrmSafetyInspectionFinding::class, 'inspection_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
