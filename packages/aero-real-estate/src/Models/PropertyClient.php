<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyClient extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_clients';

    protected $fillable = [
        'client_number', 'user_id', 'client_type', 'first_name', 'last_name',
        'email', 'phone', 'mobile_phone', 'preferred_contact_method',
        'budget_min', 'budget_max', 'preferred_areas', 'property_preferences',
        'financing_status', 'move_in_timeline', 'current_situation',
        'referral_source', 'status', 'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'preferred_areas' => 'json',
        'property_preferences' => 'json',
        'created_by' => 'integer',
    ];

    const TYPE_BUYER = 'buyer';

    const TYPE_SELLER = 'seller';

    const TYPE_RENTER = 'renter';

    const TYPE_LANDLORD = 'landlord';

    const TYPE_INVESTOR = 'investor';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_CONVERTED = 'converted';

    const STATUS_LOST = 'lost';

    const CONTACT_EMAIL = 'email';

    const CONTACT_PHONE = 'phone';

    const CONTACT_TEXT = 'text';

    const CONTACT_ANY = 'any';

    const FINANCING_PRE_APPROVED = 'pre_approved';

    const FINANCING_NEEDS_APPROVAL = 'needs_approval';

    const FINANCING_CASH_BUYER = 'cash_buyer';

    const FINANCING_UNKNOWN = 'unknown';

    const TIMELINE_IMMEDIATE = 'immediate';

    const TIMELINE_30_DAYS = '30_days';

    const TIMELINE_60_DAYS = '60_days';

    const TIMELINE_90_DAYS = '90_days';

    const TIMELINE_6_MONTHS = '6_months';

    const TIMELINE_FLEXIBLE = 'flexible';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function agents()
    {
        return $this->belongsToMany(RealEstateAgent::class, 'real_estate_agent_clients')
            ->withPivot('relationship_type', 'start_date');
    }

    public function transactions()
    {
        return $this->hasMany(PropertyTransaction::class);
    }

    public function inquiries()
    {
        return $this->hasMany(PropertyInquiry::class, 'client_id');
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getBudgetRangeAttribute()
    {
        if ($this->budget_min && $this->budget_max) {
            return '$'.number_format($this->budget_min).' - $'.number_format($this->budget_max);
        }
        if ($this->budget_min) {
            return '$'.number_format($this->budget_min).'+';
        }
        if ($this->budget_max) {
            return 'Up to $'.number_format($this->budget_max);
        }

        return 'Not specified';
    }

    public function isPotentialMatch(Property $property)
    {
        // Budget check
        if ($this->budget_max && $property->current_value > $this->budget_max) {
            return false;
        }
        if ($this->budget_min && $property->current_value < $this->budget_min) {
            return false;
        }

        // Area preference check
        if (! empty($this->preferred_areas)) {
            $propertyArea = strtolower($property->city.', '.$property->state);
            $hasAreaMatch = false;
            foreach ($this->preferred_areas as $area) {
                if (stripos($propertyArea, strtolower($area)) !== false) {
                    $hasAreaMatch = true;
                    break;
                }
            }
            if (! $hasAreaMatch) {
                return false;
            }
        }

        return true;
    }

    public function getMatchingProperties()
    {
        $query = Property::query();

        if ($this->client_type === self::TYPE_BUYER) {
            $query->where('status', Property::STATUS_AVAILABLE);
        } elseif ($this->client_type === self::TYPE_RENTER) {
            $query->where('status', Property::STATUS_AVAILABLE)
                ->whereNotNull('rental_rate');
        }

        if ($this->budget_min) {
            $query->where('current_value', '>=', $this->budget_min);
        }

        if ($this->budget_max) {
            $query->where('current_value', '<=', $this->budget_max);
        }

        return $query->get();
    }

    public function getActiveTransactionsAttribute()
    {
        return $this->transactions()
            ->whereIn('status', [PropertyTransaction::STATUS_PENDING, PropertyTransaction::STATUS_UNDER_CONTRACT])
            ->count();
    }

    public function getCompletedTransactionsAttribute()
    {
        return $this->transactions()
            ->where('status', PropertyTransaction::STATUS_CLOSED)
            ->count();
    }

    public function getTotalTransactionVolumeAttribute()
    {
        return $this->transactions()
            ->where('status', PropertyTransaction::STATUS_CLOSED)
            ->sum('sale_price');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('client_type', $type);
    }

    public function scopeBuyers($query)
    {
        return $query->where('client_type', self::TYPE_BUYER);
    }

    public function scopeSellers($query)
    {
        return $query->where('client_type', self::TYPE_SELLER);
    }

    public function scopePreApproved($query)
    {
        return $query->where('financing_status', self::FINANCING_PRE_APPROVED);
    }
}
