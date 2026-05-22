<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Core\Encryption\EncryptedField;
use Aero\Core\Models\CentralModel;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TenantBranding extends CentralModel
{
    use LogsActivity;

    protected $table = 'tenant_brandings';

    protected $fillable = [
        'tenant_id',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'custom_css_path',
        'email_from_name',
        'email_from_address',
        'dkim_selector',
        'dkim_private_key',
    ];

    protected function casts(): array
    {
        return [
            'dkim_private_key' => EncryptedField::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['dkim_private_key']);
    }
}
