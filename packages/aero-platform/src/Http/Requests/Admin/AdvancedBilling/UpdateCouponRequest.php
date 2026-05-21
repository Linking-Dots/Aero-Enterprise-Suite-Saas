<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\AdvancedBilling;

use Aero\Platform\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:64', 'uppercase', Rule::unique('coupons', 'code')->ignore($couponId)],
            'type' => ['sometimes', Rule::in([Coupon::TYPE_PERCENT, Coupon::TYPE_FIXED])],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'duration' => ['sometimes', Rule::in([Coupon::DURATION_ONCE, Coupon::DURATION_REPEATING, Coupon::DURATION_FOREVER])],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'campaign_id' => ['nullable', 'integer', 'exists:coupon_campaigns,id'],
        ];
    }
}
