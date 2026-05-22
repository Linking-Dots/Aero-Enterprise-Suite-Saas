<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VaultSecret extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'name', 'value', 'scope', 'tenant_id', 'expires_at', 'revoked_at', 'created_by',
    ];

    protected $hidden = ['value'];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
