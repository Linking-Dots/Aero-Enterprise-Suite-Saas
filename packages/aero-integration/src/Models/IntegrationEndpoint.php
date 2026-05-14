<?php

namespace Aero\Integration\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationEndpoint extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integration_endpoints';

    protected $fillable = [
        'name', 'system_type', 'endpoint_url', 'authentication_type', 'credentials',
        'headers', 'connection_timeout', 'request_timeout', 'retry_attempts',
        'status', 'rate_limit', 'rate_limit_window', 'last_health_check',
        'health_check_url', 'is_active', 'created_by', 'description',
    ];

    protected $casts = [
        'credentials' => 'encrypted:json',
        'headers' => 'json',
        'connection_timeout' => 'integer',
        'request_timeout' => 'integer',
        'retry_attempts' => 'integer',
        'rate_limit' => 'integer',
        'rate_limit_window' => 'integer',
        'last_health_check' => 'datetime',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_REST_API = 'rest_api';

    const TYPE_SOAP = 'soap';

    const TYPE_GRAPHQL = 'graphql';

    const TYPE_DATABASE = 'database';

    const TYPE_FTP = 'ftp';

    const TYPE_SFTP = 'sftp';

    const TYPE_EMAIL = 'email';

    const AUTH_NONE = 'none';

    const AUTH_BASIC = 'basic';

    const AUTH_BEARER = 'bearer';

    const AUTH_API_KEY = 'api_key';

    const AUTH_OAUTH2 = 'oauth2';

    const STATUS_HEALTHY = 'healthy';

    const STATUS_WARNING = 'warning';

    const STATUS_CRITICAL = 'critical';

    const STATUS_UNKNOWN = 'unknown';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dataSyncJobs()
    {
        return $this->hasMany(DataSyncJob::class);
    }

    public function webhookSubscriptions()
    {
        return $this->hasMany(WebhookSubscription::class);
    }

    public function connectionLogs()
    {
        return $this->hasMany(IntegrationLog::class);
    }

    public function isHealthy()
    {
        return $this->status === self::STATUS_HEALTHY;
    }

    public function needsHealthCheck($hours = 1)
    {
        if (! $this->last_health_check) {
            return true;
        }

        return $this->last_health_check->addHours($hours) <= now();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHealthy($query)
    {
        return $query->where('status', self::STATUS_HEALTHY);
    }
}
