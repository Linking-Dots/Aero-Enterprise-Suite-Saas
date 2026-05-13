<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_product_reviews';

    protected $fillable = [
        'product_id', 'customer_id', 'order_id', 'rating', 'title', 'review_text',
        'is_verified_purchase', 'is_approved', 'approved_at', 'helpful_votes',
        'not_helpful_votes', 'response_text', 'responded_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'helpful_votes' => 'integer',
        'not_helpful_votes' => 'integer',
        'responded_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewImages()
    {
        return $this->hasMany(ProductReviewImage::class);
    }

    public function reviewVotes()
    {
        return $this->hasMany(ProductReviewVote::class);
    }

    public function getHelpfulnessRatioAttribute()
    {
        $totalVotes = $this->helpful_votes + $this->not_helpful_votes;
        if ($totalVotes === 0) {
            return 0;
        }

        return round(($this->helpful_votes / $totalVotes) * 100, 1);
    }

    public function getStarDisplayAttribute()
    {
        return str_repeat('★', $this->rating).str_repeat('☆', 5 - $this->rating);
    }

    public function isApproved()
    {
        return $this->is_approved;
    }

    public function hasResponse()
    {
        return ! empty($this->response_text);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeWithRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }
}
