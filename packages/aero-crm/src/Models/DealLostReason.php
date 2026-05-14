<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;

class DealLostReason extends TenantModel
{
    protected $fillable = [
        'name',
        'description',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active reasons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
