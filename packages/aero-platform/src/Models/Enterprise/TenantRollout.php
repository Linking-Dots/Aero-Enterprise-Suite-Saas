<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantRollout extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'version_id', 'tenant_id', 'status', 'rolled_out_at', 'rolled_back_at',
    ];

    protected $casts = [
        'rolled_out_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];
}
