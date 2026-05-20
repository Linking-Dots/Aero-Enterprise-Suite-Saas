<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Core\Encryption\EncryptedField;
use Aero\Core\Models\CentralModel;

class PaymentGateway extends CentralModel
{
    protected $table = 'payment_gateways';

    protected $fillable = [
        'code',
        'label',
        'is_enabled',
        'is_default',
        'config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'config' => EncryptedField::class,
    ];
}
