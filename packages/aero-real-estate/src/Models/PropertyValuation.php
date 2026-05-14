<?php

namespace Aero\RealEstate\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyValuation extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_valuations';

    protected $fillable = [
        'property_id', 'valuation_type', 'valuation_date', 'appraiser_name',
        'appraiser_license', 'estimated_value', 'low_estimate', 'high_estimate',
        'price_per_sqft', 'comparable_properties', 'valuation_method',
        'market_conditions', 'notes', 'report_url', 'status', 'created_by',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'valuation_date' => 'date',
        'estimated_value' => 'decimal:2',
        'low_estimate' => 'decimal:2',
        'high_estimate' => 'decimal:2',
        'price_per_sqft' => 'decimal:2',
        'comparable_properties' => 'json',
        'market_conditions' => 'json',
        'created_by' => 'integer',
    ];

    const TYPE_APPRAISAL = 'appraisal';

    const TYPE_CMA = 'cma'; // Comparative Market Analysis

    const TYPE_BROKER_PRICE_OPINION = 'bpo';

    const TYPE_AUTOMATED = 'automated';

    const TYPE_INSURANCE = 'insurance';

    const TYPE_TAX_ASSESSMENT = 'tax_assessment';

    const METHOD_SALES_COMPARISON = 'sales_comparison';

    const METHOD_COST_APPROACH = 'cost_approach';

    const METHOD_INCOME_APPROACH = 'income_approach';

    const METHOD_HYBRID = 'hybrid';

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_REVIEWED = 'reviewed';

    const STATUS_EXPIRED = 'expired';

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getValueRangeAttribute()
    {
        if ($this->low_estimate && $this->high_estimate) {
            return '$'.number_format($this->low_estimate).' - $'.number_format($this->high_estimate);
        }

        return '$'.number_format($this->estimated_value);
    }

    public function getVarianceFromListingAttribute()
    {
        $property = $this->property;
        if (! $property || ! $property->current_value || ! $this->estimated_value) {
            return null;
        }

        $variance = (($this->estimated_value - $property->current_value) / $property->current_value) * 100;

        return round($variance, 2);
    }

    public function isOverpriced()
    {
        $variance = $this->getVarianceFromListingAttribute();

        return $variance !== null && $variance < -10; // Listing is 10%+ above valuation
    }

    public function isUnderpriced()
    {
        $variance = $this->getVarianceFromListingAttribute();

        return $variance !== null && $variance > 10; // Listing is 10%+ below valuation
    }

    public function isExpired()
    {
        // Valuations are typically valid for 90 days
        return $this->valuation_date && $this->valuation_date->addDays(90) < now();
    }

    public function getAccuracyRatingAttribute()
    {
        if (! $this->low_estimate || ! $this->high_estimate || ! $this->estimated_value) {
            return null;
        }

        $range = $this->high_estimate - $this->low_estimate;
        $percentageRange = ($range / $this->estimated_value) * 100;

        return match (true) {
            $percentageRange <= 5 => 'Very High',
            $percentageRange <= 10 => 'High',
            $percentageRange <= 15 => 'Medium',
            $percentageRange <= 20 => 'Low',
            default => 'Very Low'
        };
    }

    public function getComparablePropertiesCountAttribute()
    {
        return is_array($this->comparable_properties) ? count($this->comparable_properties) : 0;
    }

    public function getAverageComparablePriceAttribute()
    {
        if (! is_array($this->comparable_properties)) {
            return null;
        }

        $prices = array_column($this->comparable_properties, 'sale_price');

        return $prices ? array_sum($prices) / count($prices) : null;
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeRecent($query, $days = 90)
    {
        return $query->where('valuation_date', '>=', now()->subDays($days));
    }

    public function scopeByType($query, $type)
    {
        return $query->where('valuation_type', $type);
    }

    public function scopeExpired($query)
    {
        return $query->where('valuation_date', '<', now()->subDays(90));
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('valuation_method', $method);
    }
}
