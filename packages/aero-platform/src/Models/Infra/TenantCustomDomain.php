<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Core\Models\CentralModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TenantCustomDomain extends CentralModel
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'tenant_custom_domains';

    protected $fillable = [
        'tenant_id',
        'domain',
        'status',
        'ssl_status',
        'verified_at',
        'ssl_expires_at',
        'dns_txt_record',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
