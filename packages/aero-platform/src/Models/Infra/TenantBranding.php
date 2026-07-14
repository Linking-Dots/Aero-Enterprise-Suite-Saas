<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Infra;

use Aero\Kernel\Encryption\EncryptedField;
use Aero\Core\Models\CentralModel;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TenantBranding extends CentralModel
{
    use LogsActivity;

    protected $table = 'tenant_brandings';

    protected $fillable = [
        'tenant_id',
        'name',
        'logo_path',
        'logo_dark_path',
        'logo_icon_path',
        'favicon_path',
        'login_background_path',
        'primary_color',
        'secondary_color',
        'custom_css_path',
        'css_disabled',
        'email_from_name',
        'email_from_address',
        'dkim_selector',
        'dkim_private_key',
        'dkim_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'dkim_private_key' => EncryptedField::class,
            'css_disabled' => 'boolean',
            'dkim_verified_at' => 'datetime',
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
