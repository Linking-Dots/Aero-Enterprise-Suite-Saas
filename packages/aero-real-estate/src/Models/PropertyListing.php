<?php

namespace Aero\RealEstate\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyListing extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_listings';

    protected $fillable = [
        'property_id', 'agent_id', 'mls_number', 'listing_type', 'price',
        'price_per_sqft', 'status', 'list_date', 'expiration_date',
        'days_on_market', 'showing_instructions', 'virtual_tour_url',
        'description', 'features', 'is_featured', 'created_by',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'agent_id' => 'integer',
        'price' => 'decimal:2',
        'price_per_sqft' => 'decimal:2',
        'list_date' => 'date',
        'expiration_date' => 'date',
        'days_on_market' => 'integer',
        'features' => 'json',
        'is_featured' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_SALE = 'sale';

    const TYPE_RENT = 'rent';

    const TYPE_LEASE = 'lease';

    const STATUS_ACTIVE = 'active';

    const STATUS_PENDING = 'pending';

    const STATUS_SOLD = 'sold';

    const STATUS_RENTED = 'rented';

    const STATUS_EXPIRED = 'expired';

    const STATUS_WITHDRAWN = 'withdrawn';

    const STATUS_CANCELLED = 'cancelled';

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function agent()
    {
        return $this->belongsTo(RealEstateAgent::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inquiries()
    {
        return $this->hasMany(PropertyInquiry::class);
    }

    public function showings()
    {
        return $this->hasMany(PropertyShowing::class);
    }

    public function offers()
    {
        return $this->hasMany(PropertyOffer::class);
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired()
    {
        return $this->expiration_date && $this->expiration_date < now()->toDateString();
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function updateDaysOnMarket()
    {
        if ($this->list_date) {
            $this->days_on_market = $this->list_date->diffInDays(now());
            $this->save();
        }
    }

    public function getTotalInquiriesAttribute()
    {
        return $this->inquiries()->count();
    }

    public function getTotalShowingsAttribute()
    {
        return $this->showings()->count();
    }

    public function getActiveOffersAttribute()
    {
        return $this->offers()
            ->whereIn('status', [PropertyOffer::STATUS_PENDING, PropertyOffer::STATUS_COUNTER_OFFERED])
            ->count();
    }

    public function getHighestOfferAttribute()
    {
        return $this->offers()
            ->where('status', '!=', PropertyOffer::STATUS_WITHDRAWN)
            ->max('offer_amount');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('listing_type', $type);
    }

    public function scopeInPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('expiration_date', '<=', now()->addDays($days))
            ->where('status', self::STATUS_ACTIVE);
    }
}
