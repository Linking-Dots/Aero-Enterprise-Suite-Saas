<?php

namespace Aero\Platform\Models;

class TenantExportRequest extends CentralModel
{
    protected $table = 'tenant_export_requests';

    protected $fillable = ['tenant_id', 'requested_by', 'status', 'download_url', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];
}
