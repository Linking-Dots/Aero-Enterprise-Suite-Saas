<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

class LeaveSetting extends TenantModel
{
    /**
     * Explicit table name to avoid conflict with the legacy `leave_settings` table
     * created by 2025_12_02_120000_create_hrm_leave_tables.php.
     */
    protected $table = 'leave_global_settings';

    protected $fillable = [
        'accrual_cycle',
        'allow_negative_balance',
        'auto_approve_under_days',
        'auto_approve_threshold',
        'encashment_enabled',
        'extra',
    ];

    protected $casts = [
        'allow_negative_balance' => 'boolean',
        'auto_approve_under_days' => 'boolean',
        'auto_approve_threshold' => 'decimal:2',
        'encashment_enabled' => 'boolean',
        'extra' => 'array',
    ];
}
