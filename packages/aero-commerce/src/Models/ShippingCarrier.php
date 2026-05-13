<?php

namespace Aero\Commerce\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingCarrier extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_shipping_carriers';

    protected $fillable = [
        'name', 'code', 'api_credentials', 'tracking_url_template',
        'supported_services', 'is_active', 'logo_url', 'website_url',
        'phone_number', 'created_by',
    ];

    protected $casts = [
        'api_credentials' => 'encrypted:json',
        'supported_services' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const CARRIER_UPS = 'ups';

    const CARRIER_FEDEX = 'fedex';

    const CARRIER_USPS = 'usps';

    const CARRIER_DHL = 'dhl';

    const CARRIER_AMAZON = 'amazon';

    const CARRIER_LOCAL = 'local';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shippingMethods()
    {
        return $this->hasMany(ShippingMethod::class, 'carrier_id');
    }

    public function shipments()
    {
        return $this->hasMany(OrderShipment::class, 'carrier_id');
    }

    public function generateTrackingUrl($trackingNumber)
    {
        if (! $this->tracking_url_template || ! $trackingNumber) {
            return null;
        }

        return str_replace('{tracking_number}', $trackingNumber, $this->tracking_url_template);
    }

    public function supportsService($service)
    {
        return in_array($service, $this->supported_services ?? []);
    }

    public function getApiCredential($key, $default = null)
    {
        return $this->api_credentials[$key] ?? $default;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
