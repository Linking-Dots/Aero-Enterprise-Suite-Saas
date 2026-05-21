<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PlatformCurrency extends CentralModel
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate_to_usd',
        'is_active',
        'rate_updated_at',
    ];

    protected $casts = [
        'exchange_rate_to_usd' => 'decimal:8',
        'is_active' => 'boolean',
        'rate_updated_at' => 'datetime',
    ];

    public function regionalPrices(): HasMany
    {
        return $this->hasMany(RegionalPrice::class, 'currency_code', 'code');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
