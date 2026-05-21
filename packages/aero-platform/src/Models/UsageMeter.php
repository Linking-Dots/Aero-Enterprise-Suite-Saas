<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UsageMeter extends CentralModel
{
    use LogsActivity;

    public const AGGREGATION_SUM = 'sum';

    public const AGGREGATION_COUNT = 'count';

    public const AGGREGATION_MAX = 'max';

    protected $fillable = [
        'code',
        'name',
        'event_code',
        'aggregation',
        'price_per_unit',
        'unit_label',
        'is_active',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->useLogName('usage_meter');
    }

    public function events(): HasMany
    {
        return $this->hasMany(UsageEvent::class, 'meter_id');
    }
}
