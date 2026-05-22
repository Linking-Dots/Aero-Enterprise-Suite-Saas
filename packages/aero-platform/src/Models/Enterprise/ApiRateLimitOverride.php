<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApiRateLimitOverride extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'requests_per_minute', 'requests_per_day', 'updated_by',
    ];

    protected $casts = [
        'requests_per_minute' => 'integer',
        'requests_per_day' => 'integer',
    ];
}
