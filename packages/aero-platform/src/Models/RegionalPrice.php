<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RegionalPrice extends CentralModel
{
    use LogsActivity;

    protected $fillable = [
        'priceable_type',
        'priceable_id',
        'currency_code',
        'price_monthly',
        'price_annual',
        'is_active',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_annual' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * The polymorphic owner: Plan or Product.
     */
    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
