<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NpsSurveyResponse extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'user_email', 'score', 'comment', 'sent_at', 'responded_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];
}
