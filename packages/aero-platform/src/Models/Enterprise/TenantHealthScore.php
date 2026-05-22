<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantHealthScore extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'score', 'signals', 'trend', 'computed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'signals' => 'array',
        'computed_at' => 'datetime',
    ];
}
