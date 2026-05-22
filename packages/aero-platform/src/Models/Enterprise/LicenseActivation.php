<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivation extends CentralModel
{
    use HasUuids;

    protected $fillable = [
        'license_id', 'install_id', 'domain', 'ip_address',
        'activated_at', 'last_ping_at', 'deactivated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_ping_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'license_id');
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }
}
