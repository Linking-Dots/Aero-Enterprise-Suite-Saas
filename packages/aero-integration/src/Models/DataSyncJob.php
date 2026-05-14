<?php

namespace Aero\Integration\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataSyncJob extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integration_data_sync_jobs';

    protected $fillable = [
        'job_name', 'job_type', 'source_endpoint_id', 'target_endpoint_id',
        'sync_direction', 'entity_type', 'mapping_rules', 'transformation_rules',
        'schedule_expression', 'is_active', 'last_run_at', 'next_run_at',
        'status', 'success_count', 'error_count', 'created_by',
    ];

    protected $casts = [
        'source_endpoint_id' => 'integer',
        'target_endpoint_id' => 'integer',
        'mapping_rules' => 'json',
        'transformation_rules' => 'json',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'created_by' => 'integer',
    ];

    const TYPE_FULL_SYNC = 'full_sync';

    const TYPE_INCREMENTAL = 'incremental';

    const TYPE_REAL_TIME = 'real_time';

    const DIRECTION_IMPORT = 'import';

    const DIRECTION_EXPORT = 'export';

    const DIRECTION_BIDIRECTIONAL = 'bidirectional';

    const STATUS_PENDING = 'pending';

    const STATUS_RUNNING = 'running';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    public function sourceEndpoint()
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'source_endpoint_id');
    }

    public function targetEndpoint()
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'target_endpoint_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions()
    {
        return $this->hasMany(DataSyncExecution::class);
    }

    public function transformations()
    {
        return $this->hasMany(DataTransformation::class);
    }

    public function getLastExecution()
    {
        return $this->executions()->latest()->first();
    }

    public function getSuccessRateAttribute()
    {
        $total = $this->success_count + $this->error_count;
        if ($total === 0) {
            return 0;
        }

        return round(($this->success_count / $total) * 100, 2);
    }

    public function isDue()
    {
        return $this->is_active && $this->next_run_at && $this->next_run_at <= now();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where('next_run_at', '<=', now());
    }
}
