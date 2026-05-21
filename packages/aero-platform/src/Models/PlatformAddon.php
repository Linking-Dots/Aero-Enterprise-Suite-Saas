<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PlatformAddon extends CentralModel
{
    use LogsActivity;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'billing_period',
        'status',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->useLogName('platform_addon');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'created_by');
    }
}
