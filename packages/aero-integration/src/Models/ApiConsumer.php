<?php

namespace Aero\Integration\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiConsumer extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integration_api_consumers';

    protected $fillable = [
        'name', 'api_key', 'gateway_id', 'rate_limit', 'rate_limit_window',
        'allowed_ips', 'allowed_routes', 'is_active', 'last_used_at',
        'request_count', 'created_by', 'description',
    ];

    protected $casts = [
        'gateway_id' => 'integer',
        'rate_limit' => 'integer',
        'rate_limit_window' => 'integer',
        'allowed_ips' => 'json',
        'allowed_routes' => 'json',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'request_count' => 'integer',
        'created_by' => 'integer',
    ];

    protected $hidden = ['api_key'];

    public function gateway()
    {
        return $this->belongsTo(ApiGateway::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requests()
    {
        return $this->hasMany(ApiRequest::class, 'consumer_id');
    }

    public function canAccessRoute($route)
    {
        if (! $this->allowed_routes) {
            return true; // No restrictions
        }

        return in_array($route, $this->allowed_routes);
    }

    public function canAccessFromIp($ip)
    {
        if (! $this->allowed_ips) {
            return true; // No restrictions
        }

        return in_array($ip, $this->allowed_ips);
    }

    public function hasExceededRateLimit()
    {
        if (! $this->rate_limit) {
            return false;
        }

        $windowStart = now()->subMinutes($this->rate_limit_window ?? 60);
        $recentRequests = $this->requests()
            ->where('created_at', '>=', $windowStart)
            ->count();

        return $recentRequests >= $this->rate_limit;
    }

    public function generateApiKey()
    {
        $this->api_key = 'aero_'.bin2hex(random_bytes(32));

        return $this->api_key;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
