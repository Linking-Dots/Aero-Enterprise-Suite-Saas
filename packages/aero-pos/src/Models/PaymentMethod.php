<?php

namespace Aero\Pos\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'pos_payment_methods';

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
        'requires_authorization',
        'processing_fee',
        'icon',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_authorization' => 'boolean',
        'processing_fee' => 'decimal:2',
    ];

    /**
     * Scope for active payment methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
