<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApiQuotaOverride extends CentralModel
{
    use HasUuids;

    protected $table = 'api_quota_overrides';

    protected $fillable = [
        'tenant_id', 'resource', 'limit', 'updated_by',
    ];

    protected $casts = [
        'limit' => 'integer',
    ];
}
