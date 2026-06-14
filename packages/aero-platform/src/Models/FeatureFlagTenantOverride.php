<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * P-7 — Per-tenant override for a feature flag.
 */
class FeatureFlagTenantOverride extends CentralModel
{
    use HasFactory;

    protected $table = 'feature_flag_tenant_overrides';

    protected $fillable = [
        'flag_id',
        'tenant_id',
        'is_active',
        'set_by',
        'expires_at',
    ];

    protected $casts = [
        'flag_id' => 'integer',
        'is_active' => 'boolean',
        'set_by' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function flag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class, 'flag_id');
    }

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
