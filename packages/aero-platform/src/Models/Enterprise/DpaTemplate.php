<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DpaTemplate extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'version', 'title', 'content_md', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
