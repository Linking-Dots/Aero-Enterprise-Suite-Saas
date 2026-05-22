<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TosVersion extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'version', 'content_md', 'published_at', 'requires_re_acceptance',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'requires_re_acceptance' => 'boolean',
    ];
}
