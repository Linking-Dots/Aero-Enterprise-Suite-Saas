<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TenantModule Model
 *
 * Represents the subscription relationship between a tenant and a module/product.
 * This pivot model tracks which modules each tenant has subscribed to and their subscription status.
 *
 * @property string $tenant_id UUID of the tenant
 * @property string $module_id UUID of the module
 * @property bool $is_active Whether the module subscription is active
 * @property \Carbon\Carbon|null $subscribed_at When the tenant subscribed to the module
 * @property \Carbon\Carbon|null $unsubscribed_at When the tenant unsubscribed (if applicable)
 */
class TenantModule extends CentralModel
{
    /**
     * The connection name for the model.
     * TenantModule is stored in the landlord database, not tenant databases.
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'module_id',
        'is_active',
        'subscribed_at',
        'unsubscribed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the tenant that owns this module subscription.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the module that is subscribed to.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    /**
     * Scope to filter only active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter only inactive subscriptions.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Check if the subscription is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }
}
