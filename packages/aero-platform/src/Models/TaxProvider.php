<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Core\Casts\EncryptedField;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TaxProvider extends CentralModel
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => EncryptedField::class,
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'name', 'is_active'])->logOnlyDirty();
    }
}
