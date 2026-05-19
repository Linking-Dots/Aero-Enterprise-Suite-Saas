<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmDisciplinaryCase extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_disciplinary_cases';

    const STATUS_OPEN = 'open';

    const STATUS_AWAITING_RESPONSE = 'awaiting_response';

    const STATUS_UNDER_REVIEW = 'under_review';

    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'reference', 'employee_id', 'action_type_id', 'opened_by', 'incident_date',
        'subject', 'description', 'status', 'outcome', 'closure_notes', 'closed_at', 'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(HrmDisciplinaryActionType::class, 'action_type_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(HrmDisciplinaryCaseEvent::class, 'case_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(HrmDisciplinaryCaseDocument::class, 'case_id');
    }
}
