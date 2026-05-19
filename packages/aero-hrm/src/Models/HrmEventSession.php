<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmEventSession extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_event_sessions';

    protected $fillable = [
        'event_id',
        'title',
        'starts_at',
        'ends_at',
        'location',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(HrmEvent::class, 'event_id');
    }
}
