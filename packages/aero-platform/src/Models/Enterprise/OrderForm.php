<?php

declare(strict_types=1);

namespace Aero\Platform\Models\Enterprise;

use Aero\Platform\Models\CentralModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderForm extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'plan_id', 'product_ids', 'total_amount', 'currency',
        'status', 'msa_id', 'signed_by_name', 'signed_by_email', 'signed_at', 'created_by',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'total_amount' => 'decimal:2',
        'signed_at' => 'datetime',
    ];
}
