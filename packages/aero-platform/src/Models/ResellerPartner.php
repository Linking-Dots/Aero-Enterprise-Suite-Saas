<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ResellerPartner extends CentralModel
{
    use LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'email',
        'commission_rate',
        'status',
        'portal_slug',
        'portal_config',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:4',
        'portal_config' => 'array',
        'approved_at' => 'datetime',
    ];

    public function commissions(): HasMany
    {
        return $this->hasMany(PartnerCommission::class, 'partner_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email', 'commission_rate', 'status'])->logOnlyDirty();
    }
}
