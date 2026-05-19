<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmEventRegistration extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_event_registrations';

    const STATUS_REGISTERED = 'registered';

    const STATUS_ATTENDED = 'attended';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'event_id',
        'session_id',
        'employee_id',
        'attendee_name',
        'attendee_email',
        'token',
        'status',
        'registered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(HrmEvent::class, 'event_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(HrmEventSession::class, 'session_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
