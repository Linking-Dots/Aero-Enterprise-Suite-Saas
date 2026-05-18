<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Aero\HRM\Database\Factories\Feedback360RequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback360Request extends TenantModel
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'hrm_feedback_360_requests';

    /** @var list<string> */
    protected $fillable = [
        'subject_employee_id',
        'requester_id',
        'respondent_ids',
        'due_on',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'respondent_ids' => 'array',
            'due_on' => 'date',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'subject_employee_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    protected static function newFactory(): Feedback360RequestFactory
    {
        return Feedback360RequestFactory::new();
    }
}
