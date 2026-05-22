<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ScimSyncLog extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'operation', 'resource_type', 'resource_id',
        'status', 'payload', 'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
