<?php

namespace Aero\Platform\Models;

class TenantProvisioningLog extends CentralModel
{
    protected $table = 'tenant_provisioning_logs';

    protected $fillable = ['tenant_id', 'status', 'step', 'message'];
}
