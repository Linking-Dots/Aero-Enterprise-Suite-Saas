<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProvisioningLog extends CentralModel
{
    protected $table = 'tenant_provisioning_logs';

    protected $fillable = ['tenant_id', 'status', 'step', 'message'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
