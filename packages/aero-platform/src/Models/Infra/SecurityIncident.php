<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SecurityIncident extends CentralModel
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'security_incidents';

    protected $fillable = [
        'title',
        'severity',
        'description',
        'status',
        'affected_tenants',
        'resolved_at',
        'notified_tenants_at',
    ];

    protected function casts(): array
    {
        return [
            'affected_tenants' => 'array',
            'resolved_at' => 'datetime',
            'notified_tenants_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
