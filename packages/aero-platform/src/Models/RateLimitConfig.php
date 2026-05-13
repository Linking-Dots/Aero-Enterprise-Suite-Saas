<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Rate Limit Configuration Model
 *
 * Stores rate limiting configurations for tenants and global settings
 */
class RateLimitConfig extends CentralModel
{
    use HasUuids;

    protected $table = 'rate_limit_configs';

    protected $fillable = [
        'tenant_id',
        'limit_type',
        'max_requests',
        'time_window_seconds',
        'burst_limit',
        'throttle_percentage',
        'block_duration_seconds',
        'whitelist_ips',
        'blacklist_ips',
        'is_active',
    ];

    protected $casts = [
        'max_requests' => 'integer',
        'time_window_seconds' => 'integer',
        'burst_limit' => 'integer',
        'throttle_percentage' => 'integer',
        'block_duration_seconds' => 'integer',
        'whitelist_ips' => 'array',
        'blacklist_ips' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the tenant that owns this configuration
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope for global configurations
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Scope for tenant-specific configurations
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
