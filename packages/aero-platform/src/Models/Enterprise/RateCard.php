<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class RateCard extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'tenant_id', 'line_items', 'is_active', 'created_by',
    ];

    protected $casts = [
        'line_items' => 'array',
        'is_active' => 'boolean',
    ];
}
