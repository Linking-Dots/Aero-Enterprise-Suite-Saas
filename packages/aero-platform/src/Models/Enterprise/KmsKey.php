<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KmsKey extends CentralModel
{
    use HasUuids;

    protected $table = 'kms_keys';

    protected $fillable = [
        'provider', 'key_id', 'status', 'rotated_at', 'rotated_by',
    ];

    protected $casts = [
        'rotated_at' => 'datetime',
    ];
}
