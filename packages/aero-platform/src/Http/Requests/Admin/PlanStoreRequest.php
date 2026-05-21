<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanStoreRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('plans', 'slug')],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'price_annual' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3'],
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

            // Backwards-compat fields that may come from existing UI
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

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }

        $this->merge([
            'is_public' => $this->boolean('is_public', true),
            'is_active' => $this->boolean('is_active', true),
            'is_featured' => $this->boolean('is_featured', false),
            'status' => $this->input('status', 'active'),
        ]);
    }
}
