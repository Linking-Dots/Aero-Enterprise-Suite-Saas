<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\AdvancedBilling;

use Aero\Platform\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkGenerateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'integer', 'exists:coupon_campaigns,id'],
            'prefix' => ['required', 'string', 'max:16', 'alpha_num'],
            'count' => ['required', 'integer', 'min:1', 'max:1000'],
            'options' => ['sometimes', 'array'],
            'options.type' => ['sometimes', Rule::in([Coupon::TYPE_PERCENT, Coupon::TYPE_FIXED])],
            'options.value' => ['sometimes', 'numeric', 'min:0'],
            'options.currency' => ['nullable', 'string', 'size:3'],
            'options.duration' => ['sometimes', Rule::in([Coupon::DURATION_ONCE, Coupon::DURATION_REPEATING, Coupon::DURATION_FOREVER])],
            'options.duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'options.max_redemptions' => ['nullable', 'integer', 'min:1'],
            'options.expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
