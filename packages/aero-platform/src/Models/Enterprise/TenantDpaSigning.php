<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDpaSigning extends CentralModel
{
    use HasUuids;

    protected $table = 'tenant_dpa_signings';

    protected $fillable = [
        'tenant_id', 'template_id', 'signed_by_name', 'signed_by_email', 'signed_at', 'ip_address',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DpaTemplate::class, 'template_id');
    }
}
