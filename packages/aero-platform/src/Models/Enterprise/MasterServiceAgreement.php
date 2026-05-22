<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterServiceAgreement extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'master_service_agreements';

    protected $fillable = [
        'tenant_id', 'title', 'content_md', 'status',
        'signed_by_name', 'signed_by_email', 'signed_by_ip', 'signed_at', 'created_by',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];
}
