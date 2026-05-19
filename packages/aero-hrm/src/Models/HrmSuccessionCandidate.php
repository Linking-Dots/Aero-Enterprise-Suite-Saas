<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmSuccessionCandidate extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_succession_candidates';

    const READINESS_READY_NOW = 'ready_now';

    const READINESS_1_2_YEARS = '1_2_years';

    const READINESS_3_5_YEARS = '3_5_years';

    protected $fillable = [
        'role_id',
        'employee_id',
        'readiness',
        'notes',
        'nominated_by',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'role_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function nominatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }
}
