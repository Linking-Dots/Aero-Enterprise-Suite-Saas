<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ScimEndpoint extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'token', 'provider', 'is_active', 'token_rotated_at',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'is_active' => 'boolean',
        'token_rotated_at' => 'datetime',
    ];
}
