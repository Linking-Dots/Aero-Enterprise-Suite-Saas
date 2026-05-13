<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReviewImage extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_product_review_images';

    protected $fillable = [
        'product_review_id', 'image_path', 'image_url', 'alt_text', 'sort_order',
    ];

    protected $casts = [
        'product_review_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function productReview()
    {
        return $this->belongsTo(ProductReview::class);
    }

    public function getImageUrlAttribute($value)
    {
        if ($value) {
            return $value;
        }

        if ($this->image_path) {
            return asset('storage/'.$this->image_path);
        }

        return null;
    }
}
