<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantQuotaOverride extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';

    protected $table = 'tenant_quota_overrides';

    protected $fillable = [
        'tenant_id',
        'resource',
        'limit_value',
        'reason',
        'expires_at',
        'set_by',
    ];

    protected function casts(): array
    {
        return [
            'limit_value' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function setter(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'set_by');
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
