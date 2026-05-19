<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmSafetyIncident extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_safety_incidents';

    const STATUS_REPORTED = 'reported';

    const STATUS_INVESTIGATING = 'investigating';

    const STATUS_CLOSED = 'closed';

    const TYPE_INJURY = 'injury';

    const TYPE_NEAR_MISS = 'near_miss';

    const TYPE_PROPERTY_DAMAGE = 'property_damage';

    protected $fillable = [
        'reference',
        'occurred_at',
        'type',
        'severity',
        'department_id',
        'location',
        'description',
        'injured_person_ids',
        'status',
        'reported_by',
        'closed_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'closed_at' => 'datetime',
            'injured_person_ids' => 'array',
        ];
    }

    public function investigations(): HasMany
    {
        return $this->hasMany(HrmSafetyIncidentInvestigation::class, 'incident_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
