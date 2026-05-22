<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DsarRequest extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'requester_email', 'request_type', 'status', 'notes', 'fulfilled_at', 'fulfilled_by',
    ];

    protected $casts = [
        'fulfilled_at' => 'datetime',
    ];
}
