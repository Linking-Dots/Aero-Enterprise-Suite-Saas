<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuccessPlaybook extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'steps', 'trigger', 'is_active',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
    ];
}
