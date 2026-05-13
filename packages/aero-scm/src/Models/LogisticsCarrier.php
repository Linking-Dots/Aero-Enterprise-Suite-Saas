<?php

namespace Aero\Scm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsCarrier extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_info',
        'website',
        'tracking_url_format',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the shipments for this carrier.
     */
    public function shipments()
    {
        return $this->hasMany(LogisticsShipment::class, 'carrier_id');
    }

    /**
     * Scope to get only active carriers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
