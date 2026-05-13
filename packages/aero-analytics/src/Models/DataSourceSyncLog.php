<?php

namespace Aero\Analytics\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSourceSyncLog extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_data_source_sync_logs';

    protected $fillable = [
        'data_source_id', 'sync_type', 'status', 'started_at', 'completed_at',
        'records_processed', 'records_inserted', 'records_updated', 'records_failed',
        'error_message', 'sync_config', 'triggered_by',
    ];

    protected $casts = [
        'data_source_id' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'records_processed' => 'integer',
        'records_inserted' => 'integer',
        'records_updated' => 'integer',
        'records_failed' => 'integer',
        'sync_config' => 'json',
        'triggered_by' => 'integer',
    ];

    const TYPE_FULL = 'full';

    const TYPE_INCREMENTAL = 'incremental';

    const TYPE_DELTA = 'delta';

    const STATUS_RUNNING = 'running';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function getDurationAttribute()
    {
        if ($this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }

        return $this->started_at->diffInSeconds(now());
    }

    public function getSuccessRateAttribute()
    {
        return $this->records_processed > 0 ?
            (($this->records_processed - $this->records_failed) / $this->records_processed) * 100 : 0;
    }
}
