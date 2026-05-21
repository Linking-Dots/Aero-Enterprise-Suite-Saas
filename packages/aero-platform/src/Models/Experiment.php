<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * P-7 — A/B Experiment tied to a feature flag.
 */
class Experiment extends CentralModel
{
    use HasFactory;

    protected $table = 'feature_flag_experiments';

    protected $fillable = [
        'name',
        'flag_id',
        'control_pct',
        'variant_pct',
        'started_at',
        'stopped_at',
        'winner',
    ];

    protected $casts = [
        'flag_id' => 'integer',
        'control_pct' => 'integer',
        'variant_pct' => 'integer',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function flag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class, 'flag_id');
    }

    public function getAuditLabel(): string
    {
        return "Experiment: {$this->name}";
    }
}
