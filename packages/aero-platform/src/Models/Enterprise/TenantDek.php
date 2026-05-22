<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantDek extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'kms_key_id', 'status', 'rotated_at',
    ];

    protected $casts = [
        'rotated_at' => 'datetime',
    ];
}
