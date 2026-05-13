<?php

namespace Aero\Analytics\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataSource extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'analytics_data_sources';

    protected $fillable = [
        'name', 'description', 'source_type', 'connection_config',
        'schema_config', 'is_active', 'created_by', 'last_sync_at',
    ];

    protected $casts = [
        'connection_config' => 'json',
        'schema_config' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    const TYPE_DATABASE = 'database';

    const TYPE_API = 'api';

    const TYPE_FILE = 'file';

    const TYPE_WAREHOUSE = 'warehouse';

    const TYPE_CLOUD = 'cloud';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function widgets()
    {
        return $this->hasMany(Widget::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function kpis()
    {
        return $this->hasMany(KPI::class);
    }

    public function syncLogs()
    {
        return $this->hasMany(DataSourceSyncLog::class);
    }
}
