<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ObservabilityAlert extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'name', 'metric', 'condition', 'threshold', 'severity', 'is_active', 'channels',
    ];

    protected $casts = [
        'threshold' => 'decimal:4',
        'is_active' => 'boolean',
        'channels' => 'array',
    ];
}
