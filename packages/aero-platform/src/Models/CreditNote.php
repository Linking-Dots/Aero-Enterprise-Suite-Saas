<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CreditNote extends CentralModel
{
    use LogsActivity;

    public const STATUS_OPEN = 'open';

    public const STATUS_PARTIALLY_APPLIED = 'partially_applied';

    public const STATUS_FULLY_APPLIED = 'fully_applied';

    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'reference',
        'tenant_id',
        'amount',
        'currency',
        'reason',
        'amount_used',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_used' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->useLogName('credit_note');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function availableBalance(): float
    {
        return (float) $this->amount - (float) $this->amount_used;
    }
}
