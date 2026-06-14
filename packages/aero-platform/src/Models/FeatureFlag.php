<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * P-7 — Feature Flag.
 *
 * Global flag with optional per-tenant overrides and A/B experiments.
 */
class FeatureFlag extends CentralModel
{
    use HasFactory;

    protected $table = 'feature_flags';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'rollout_pct',
        'is_archived',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rollout_pct' => 'integer',
        'is_archived' => 'boolean',
        'created_by' => 'integer',
    ];

    public function tenantOverrides(): HasMany
    {
        return $this->hasMany(FeatureFlagTenantOverride::class, 'flag_id');
    }

    public function experiments(): HasMany
    {
        return $this->hasMany(Experiment::class, 'flag_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAuditLabel(): string
    {
        return "Feature Flag: {$this->code}";
    }
}
