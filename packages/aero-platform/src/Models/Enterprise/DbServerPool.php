<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DbServerPool extends CentralModel
{
    use HasUuids;

    protected $table = 'db_server_pools';

    protected $fillable = [
        'name', 'host', 'port', 'max_connections', 'current_tenants', 'region_code', 'is_active',
    ];

    protected $casts = [
        'port' => 'integer',
        'max_connections' => 'integer',
        'current_tenants' => 'integer',
        'is_active' => 'boolean',
    ];
}
