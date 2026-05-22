<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DrDrill extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'runbook_id', 'status', 'scheduled_at', 'executed_at', 'outcome', 'scheduled_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
    ];
}
