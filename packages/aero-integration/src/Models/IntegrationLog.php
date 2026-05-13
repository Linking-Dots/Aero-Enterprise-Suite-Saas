<?php

namespace Aero\Integration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends TenantModel
{
    use HasFactory;

    protected $table = 'integration_logs';

    protected $fillable = [
        'integration_endpoint_id', 'log_type', 'level', 'message', 'context',
        'request_data', 'response_data', 'duration_ms', 'user_id', 'ip_address',
    ];

    protected $casts = [
        'integration_endpoint_id' => 'integer',
        'context' => 'json',
        'request_data' => 'json',
        'response_data' => 'json',
        'duration_ms' => 'integer',
        'user_id' => 'integer',
    ];

    const TYPE_API_REQUEST = 'api_request';

    const TYPE_WEBHOOK = 'webhook';

    const TYPE_DATA_SYNC = 'data_sync';

    const TYPE_WORKFLOW = 'workflow';

    const TYPE_HEALTH_CHECK = 'health_check';

    const TYPE_AUTHENTICATION = 'authentication';

    const LEVEL_DEBUG = 'debug';

    const LEVEL_INFO = 'info';

    const LEVEL_WARNING = 'warning';

    const LEVEL_ERROR = 'error';

    const LEVEL_CRITICAL = 'critical';

    public function integrationEndpoint()
    {
        return $this->belongsTo(IntegrationEndpoint::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isError()
    {
        return in_array($this->level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('log_type', $type);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
