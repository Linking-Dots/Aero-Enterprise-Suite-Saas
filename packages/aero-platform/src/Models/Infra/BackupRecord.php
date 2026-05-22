<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BackupRecord extends CentralModel
{
    use LogsActivity;

    protected $table = 'backup_records';

    protected $fillable = [
        'tenant_id',
        'schedule_id',
        'size_bytes',
        'storage_path',
        'storage_backend',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(BackupSchedule::class, 'schedule_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
