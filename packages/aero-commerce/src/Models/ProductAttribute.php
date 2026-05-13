<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttribute extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_product_attributes';

    protected $fillable = [
        'name', 'slug', 'attribute_type', 'is_required', 'is_filterable',
        'is_variation', 'sort_order', 'options',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_variation' => 'boolean',
        'sort_order' => 'integer',
        'options' => 'json',
    ];

    const TYPE_TEXT = 'text';

    const TYPE_TEXTAREA = 'textarea';

    const TYPE_SELECT = 'select';

    const TYPE_MULTISELECT = 'multiselect';

    const TYPE_BOOLEAN = 'boolean';

    const TYPE_NUMBER = 'number';

    const TYPE_DATE = 'date';

    const TYPE_COLOR = 'color';

    const TYPE_IMAGE = 'image';

    public function products()
    {
        return $this->belongsToMany(Product::class, 'commerce_product_attribute_values')
            ->withPivot('value', 'price_modifier');
    }

    public function getFormattedOptionsAttribute()
    {
        if (in_array($this->attribute_type, [self::TYPE_SELECT, self::TYPE_MULTISELECT])) {
            return collect($this->options)->map(function ($option) {
                return is_array($option) ? $option : ['label' => $option, 'value' => $option];
            });
        }

        return collect();
    }
}
