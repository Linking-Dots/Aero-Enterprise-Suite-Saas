<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmWarning extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_warnings';

    const STATUS_ISSUED = 'issued';

    const STATUS_ACKNOWLEDGED = 'acknowledged';

    const STATUS_ESCALATED = 'escalated';

    protected $fillable = [
        'employee_id', 'issued_by', 'action_type_id', 'subject', 'body',
        'status', 'issued_at', 'acknowledged_at', 'employee_response', 'escalated_to_case_id',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(HrmDisciplinaryActionType::class, 'action_type_id');
    }

    public function escalatedToCase(): BelongsTo
    {
        return $this->belongsTo(HrmDisciplinaryCase::class, 'escalated_to_case_id');
    }
}
