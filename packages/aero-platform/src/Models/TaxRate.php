<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TaxRate extends CentralModel
{
    use LogsActivity;

    protected $fillable = [
        'country_code',
        'region_code',
        'name',
        'rate',
        'type',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
