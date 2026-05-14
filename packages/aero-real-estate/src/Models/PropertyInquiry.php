<?php

namespace Aero\RealEstate\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyInquiry extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_inquiries';

    protected $fillable = [
        'property_listing_id', 'inquirer_name', 'inquirer_email', 'inquirer_phone',
        'inquiry_type', 'message', 'preferred_contact_method', 'preferred_contact_time',
        'viewing_date_requested', 'budget_range_min', 'budget_range_max',
        'financing_preapproved', 'agent_id', 'status', 'follow_up_date',
        'notes', 'source', 'created_by',
    ];

    protected $casts = [
        'property_listing_id' => 'integer',
        'viewing_date_requested' => 'date',
        'budget_range_min' => 'decimal:2',
        'budget_range_max' => 'decimal:2',
        'financing_preapproved' => 'boolean',
        'agent_id' => 'integer',
        'follow_up_date' => 'date',
        'created_by' => 'integer',
    ];

    const TYPE_GENERAL_INFO = 'general_info';

    const TYPE_SCHEDULE_VIEWING = 'schedule_viewing';

    const TYPE_PRICE_INQUIRY = 'price_inquiry';

    const TYPE_NEIGHBORHOOD_INFO = 'neighborhood_info';

    const TYPE_FINANCING_OPTIONS = 'financing_options';

    const TYPE_MAKE_OFFER = 'make_offer';

    const STATUS_NEW = 'new';

    const STATUS_CONTACTED = 'contacted';

    const STATUS_QUALIFIED = 'qualified';

    const STATUS_SHOWING_SCHEDULED = 'showing_scheduled';

    const STATUS_CONVERTED = 'converted';

    const STATUS_LOST = 'lost';

    const STATUS_SPAM = 'spam';

    const CONTACT_EMAIL = 'email';

    const CONTACT_PHONE = 'phone';

    const CONTACT_TEXT = 'text';

    const CONTACT_ANY = 'any';

    const SOURCE_WEBSITE = 'website';

    const SOURCE_REFERRAL = 'referral';

    const SOURCE_SIGN = 'sign';

    const SOURCE_MLS = 'mls';

    const SOURCE_SOCIAL_MEDIA = 'social_media';

    const SOURCE_ADVERTISEMENT = 'advertisement';

    const SOURCE_WALK_IN = 'walk_in';

    public function propertyListing()
    {
        return $this->belongsTo(PropertyListing::class);
    }

    public function agent()
    {
        return $this->belongsTo(RealEstateAgent::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followUps()
    {
        return $this->hasMany(InquiryFollowUp::class);
    }

    public function isNew()
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isQualified()
    {
        return $this->status === self::STATUS_QUALIFIED;
    }

    public function isConverted()
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function needsFollowUp()
    {
        return $this->follow_up_date &&
               $this->follow_up_date <= now()->toDateString() &&
               ! in_array($this->status, [self::STATUS_CONVERTED, self::STATUS_LOST]);
    }

    public function getBudgetRangeAttribute()
    {
        if ($this->budget_range_min && $this->budget_range_max) {
            return '$'.number_format($this->budget_range_min).' - $'.number_format($this->budget_range_max);
        }
        if ($this->budget_range_min) {
            return '$'.number_format($this->budget_range_min).'+';
        }
        if ($this->budget_range_max) {
            return 'Up to $'.number_format($this->budget_range_max);
        }

        return 'Not specified';
    }

    public function getLeadQualityAttribute()
    {
        $score = 0;

        // Budget alignment
        if ($this->propertyListing && $this->budget_range_min && $this->budget_range_max) {
            $listingPrice = $this->propertyListing->price;
            if ($listingPrice >= $this->budget_range_min && $listingPrice <= $this->budget_range_max) {
                $score += 3;
            } elseif ($listingPrice <= $this->budget_range_max * 1.1) {
                $score += 2;
            }
        }

        // Financing pre-approval
        if ($this->financing_preapproved) {
            $score += 2;
        }

        // Contact information completeness
        if ($this->inquirer_phone && $this->inquirer_email) {
            $score += 1;
        }

        // Inquiry type
        if (in_array($this->inquiry_type, [self::TYPE_SCHEDULE_VIEWING, self::TYPE_MAKE_OFFER])) {
            $score += 2;
        }

        return match (true) {
            $score >= 7 => 'Hot',
            $score >= 5 => 'Warm',
            $score >= 3 => 'Cold',
            default => 'Unqualified'
        };
    }

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeQualified($query)
    {
        return $query->where('status', self::STATUS_QUALIFIED);
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->where('follow_up_date', '<=', now())
            ->whereNotIn('status', [self::STATUS_CONVERTED, self::STATUS_LOST]);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopePreApproved($query)
    {
        return $query->where('financing_preapproved', true);
    }
}
