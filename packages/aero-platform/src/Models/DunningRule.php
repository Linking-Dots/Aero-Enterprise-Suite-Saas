<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DunningRule extends CentralModel
{
    use LogsActivity;

    public const ACTION_RETRY = 'retry';

    public const ACTION_EMAIL = 'email';

    public const ACTION_SUSPEND = 'suspend';

    public const ACTION_MARK_UNPAID = 'mark_unpaid';

    protected $fillable = [
        'name',
        'day_offset',
        'action',
        'email_template_id',
        'is_active',
        'order_index',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'day_offset' => 'integer',
        'order_index' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->useLogName('dunning_rule');
    }
}
