<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReleaseVersion extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'version', 'channel', 'notes', 'is_published', 'published_at', 'published_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];
}
