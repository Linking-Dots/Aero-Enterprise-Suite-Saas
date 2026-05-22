<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StatusIncident extends CentralModel
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'status_incidents';

    protected $fillable = [
        'title',
        'status',
        'impact',
        'component_ids',
        'started_at',
        'resolved_at',
        'updates',
    ];

    protected function casts(): array
    {
        return [
            'component_ids' => 'array',
            'updates' => 'array',
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function postmortem(): HasOne
    {
        return $this->hasOne(StatusPagePostmortem::class, 'incident_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
