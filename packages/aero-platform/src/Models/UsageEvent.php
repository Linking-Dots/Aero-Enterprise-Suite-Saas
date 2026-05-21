<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageEvent extends CentralModel
{
    protected $fillable = [
        'meter_id',
        'tenant_id',
        'quantity',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function meter(): BelongsTo
    {
        return $this->belongsTo(UsageMeter::class, 'meter_id');
    }
}
