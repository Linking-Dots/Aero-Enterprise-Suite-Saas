<?php

namespace Aero\Integration\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiGateway extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integration_api_gateways';

    protected $fillable = [
        'name', 'base_url', 'version', 'description', 'authentication_required',
        'rate_limiting_enabled', 'rate_limit', 'rate_limit_window',
        'allowed_origins', 'allowed_methods', 'allowed_headers',
        'request_logging_enabled', 'response_caching_enabled', 'cache_ttl',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'authentication_required' => 'boolean',
        'rate_limiting_enabled' => 'boolean',
        'rate_limit' => 'integer',
        'rate_limit_window' => 'integer',
        'allowed_origins' => 'json',
        'allowed_methods' => 'json',
        'allowed_headers' => 'json',
        'request_logging_enabled' => 'boolean',
        'response_caching_enabled' => 'boolean',
        'cache_ttl' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function routes()
    {
        return $this->hasMany(ApiRoute::class, 'gateway_id');
    }

    public function consumers()
    {
        return $this->hasMany(ApiConsumer::class, 'gateway_id');
    }

    public function requests()
    {
        return $this->hasMany(ApiRequest::class, 'gateway_id');
    }

    public function getRequestStatsAttribute()
    {
        $today = now()->startOfDay();

        return [
            'today' => $this->requests()->where('created_at', '>=', $today)->count(),
            'this_week' => $this->requests()->where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => $this->requests()->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    public function getErrorRateAttribute()
    {
        $total = $this->requests()->count();
        if ($total === 0) {
            return 0;
        }

        $errors = $this->requests()->where('response_status', '>=', 400)->count();

        return round(($errors / $total) * 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
