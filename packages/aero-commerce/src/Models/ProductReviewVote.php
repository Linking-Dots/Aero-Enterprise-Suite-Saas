<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReviewVote extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_product_review_votes';

    protected $fillable = [
        'product_review_id', 'customer_id', 'vote_type', 'ip_address',
    ];

    protected $casts = [
        'product_review_id' => 'integer',
        'customer_id' => 'integer',
    ];

    const VOTE_HELPFUL = 'helpful';

    const VOTE_NOT_HELPFUL = 'not_helpful';

    public function productReview()
    {
        return $this->belongsTo(ProductReview::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function isHelpful()
    {
        return $this->vote_type === self::VOTE_HELPFUL;
    }

    public function isNotHelpful()
    {
        return $this->vote_type === self::VOTE_NOT_HELPFUL;
    }

    public function scopeHelpful($query)
    {
        return $query->where('vote_type', self::VOTE_HELPFUL);
    }

    public function scopeNotHelpful($query)
    {
        return $query->where('vote_type', self::VOTE_NOT_HELPFUL);
    }
}
