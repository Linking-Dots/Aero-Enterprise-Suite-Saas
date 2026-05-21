<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin;

use Aero\Platform\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // HRMAC middleware handles authorisation
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Plan $plan */
        $plan = $this->route('plan');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('plans', 'slug')->ignore($plan?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'price_annual' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'status' => ['nullable', 'string', Rule::in(['active', 'archived', 'draft'])],
            'is_public' => ['boolean'],

            // Feature/limit JSON blobs
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'limits' => ['nullable', 'array'],

            // Stripe
            'stripe_price_id_monthly' => ['nullable', 'string', 'max:255'],
            'stripe_price_id_annual' => ['nullable', 'string', 'max:255'],

            // Backwards-compat
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'visibility' => ['nullable', 'string', Rule::in(['public', 'private', 'hidden'])],
            'tier' => ['nullable', 'string', Rule::in(['free', 'starter', 'professional', 'enterprise'])],
            'plan_type' => ['nullable', 'string', Rule::in(['trial', 'free', 'paid', 'custom'])],
            'stripe_product_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
