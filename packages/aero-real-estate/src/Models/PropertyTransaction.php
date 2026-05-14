<?php

namespace Aero\RealEstate\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyTransaction extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_transactions';

    protected $fillable = [
        'property_id', 'listing_id', 'agent_id', 'buyer_agent_id', 'client_id',
        'transaction_number', 'transaction_type', 'sale_price', 'commission_rate',
        'agent_commission', 'buyer_agent_commission', 'contract_date',
        'closing_date', 'status', 'financing_type', 'loan_amount',
        'down_payment', 'closing_costs', 'notes', 'created_by',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'listing_id' => 'integer',
        'agent_id' => 'integer',
        'buyer_agent_id' => 'integer',
        'client_id' => 'integer',
        'sale_price' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'agent_commission' => 'decimal:2',
        'buyer_agent_commission' => 'decimal:2',
        'contract_date' => 'date',
        'closing_date' => 'date',
        'loan_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'closing_costs' => 'decimal:2',
        'created_by' => 'integer',
    ];

    const TYPE_SALE = 'sale';

    const TYPE_PURCHASE = 'purchase';

    const TYPE_LEASE = 'lease';

    const STATUS_PENDING = 'pending';

    const STATUS_UNDER_CONTRACT = 'under_contract';

    const STATUS_CLOSED = 'closed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_FAILED = 'failed';

    const FINANCING_CASH = 'cash';

    const FINANCING_CONVENTIONAL = 'conventional';

    const FINANCING_FHA = 'fha';

    const FINANCING_VA = 'va';

    const FINANCING_USDA = 'usda';

    const FINANCING_OTHER = 'other';

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function listing()
    {
        return $this->belongsTo(PropertyListing::class);
    }

    public function agent()
    {
        return $this->belongsTo(RealEstateAgent::class);
    }

    public function buyerAgent()
    {
        return $this->belongsTo(RealEstateAgent::class, 'buyer_agent_id');
    }

    public function client()
    {
        return $this->belongsTo(PropertyClient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isUnderContract()
    {
        return $this->status === self::STATUS_UNDER_CONTRACT;
    }

    public function getTotalCommissionAttribute()
    {
        return ($this->agent_commission ?? 0) + ($this->buyer_agent_commission ?? 0);
    }

    public function getLoanToValueRatioAttribute()
    {
        if ($this->loan_amount && $this->sale_price) {
            return round(($this->loan_amount / $this->sale_price) * 100, 2);
        }

        return 0;
    }

    public function getNetProceedsAttribute()
    {
        return $this->sale_price - $this->getTotalCommissionAttribute() - ($this->closing_costs ?? 0);
    }

    public function getDaysToCloseAttribute()
    {
        if ($this->contract_date && $this->closing_date) {
            return $this->contract_date->diffInDays($this->closing_date);
        }

        return null;
    }

    public function isOverdue()
    {
        return $this->closing_date &&
               $this->closing_date < now()->toDateString() &&
               ! $this->isClosed();
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnderContract($query)
    {
        return $query->where('status', self::STATUS_UNDER_CONTRACT);
    }

    public function scopeBySalePrice($query, $minPrice, $maxPrice = null)
    {
        $query->where('sale_price', '>=', $minPrice);
        if ($maxPrice) {
            $query->where('sale_price', '<=', $maxPrice);
        }

        return $query;
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where(function ($q) use ($agentId) {
            $q->where('agent_id', $agentId)
                ->orWhere('buyer_agent_id', $agentId);
        });
    }
}
