<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmGrievance extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_grievances';

    const STATUS_FILED = 'filed';

    const STATUS_UNDER_INVESTIGATION = 'under_investigation';

    const STATUS_RESOLVED = 'resolved';

    const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'reference', 'filed_by', 'against_employee_id', 'category', 'subject',
        'description', 'confidentiality', 'status', 'investigator_id',
        'resolution_notes', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function filedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'filed_by');
    }

    public function againstEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'against_employee_id');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(HrmGrievanceEvent::class, 'grievance_id');
    }
}
