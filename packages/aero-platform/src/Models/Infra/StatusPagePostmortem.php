<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StatusPagePostmortem extends CentralModel
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'status_page_postmortems';

    protected $fillable = [
        'incident_id',
        'title',
        'content_md',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(StatusIncident::class, 'incident_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
