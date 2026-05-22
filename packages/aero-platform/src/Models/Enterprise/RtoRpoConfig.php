<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RtoRpoConfig extends CentralModel
{
    use HasUuids;

    protected $table = 'rto_rpo_configs';

    protected $fillable = [
        'service', 'rto_minutes', 'rpo_minutes', 'updated_by',
    ];

    protected $casts = [
        'rto_minutes' => 'integer',
        'rpo_minutes' => 'integer',
    ];
}
