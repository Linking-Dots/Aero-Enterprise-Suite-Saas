<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUsageEvent extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';

    protected $table = 'feature_usage_events';

    protected $fillable = ['tenant_id', 'feature_code', 'user_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
