<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BackupSchedule extends CentralModel
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'backup_schedules';

    protected $fillable = [
        'tenant_id',
        'frequency',
        'time_of_day',
        'retention_days',
        'storage_backend_code',
        'is_active',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'retention_days' => 'integer',
        ];
    }

    public function records(): HasMany
    {
        return $this->hasMany(BackupRecord::class, 'schedule_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
