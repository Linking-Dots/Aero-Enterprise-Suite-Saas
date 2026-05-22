<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Region extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'code', 'name', 'db_host', 'is_active', 'is_default', 'cdn_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'cdn_config' => 'array',
    ];
}
