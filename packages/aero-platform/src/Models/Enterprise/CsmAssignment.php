<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CsmAssignment extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'csm_user_id', 'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];
}
