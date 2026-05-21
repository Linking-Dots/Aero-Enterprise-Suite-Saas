<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvoiceTemplate extends CentralModel
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'html_body',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'is_default'])->logOnlyDirty();
    }
}
