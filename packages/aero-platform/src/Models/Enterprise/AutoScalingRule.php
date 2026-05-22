<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AutoScalingRule extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'resource_type', 'metric', 'scale_up_threshold', 'scale_down_threshold',
        'min_instances', 'max_instances', 'is_enabled',
    ];

    protected $casts = [
        'scale_up_threshold' => 'decimal:2',
        'scale_down_threshold' => 'decimal:2',
        'min_instances' => 'integer',
        'max_instances' => 'integer',
        'is_enabled' => 'boolean',
    ];
}
