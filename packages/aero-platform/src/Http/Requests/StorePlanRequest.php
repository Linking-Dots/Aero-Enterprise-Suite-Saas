<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Form Request for storing a new Plan.
 */
class StorePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by route middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('plans', 'slug')],
            'tier' => ['required', 'string', Rule::in(['free', 'starter', 'professional', 'enterprise'])],
            'plan_type' => ['required', 'string', Rule::in(['trial', 'free', 'paid', 'custom'])],
            'description' => ['nullable', 'string', 'max:1000'],

            // Pricing
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'yearly_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'setup_fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3'],

            // Trial & Lifecycle
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'grace_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'downgrade_policy' => ['nullable', 'string', Rule::in(['immediate', 'end_of_period'])],
            'cancellation_policy' => ['nullable', 'string', Rule::in(['immediate', 'end_of_period'])],

            // Status & Visibility
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'visibility' => ['required', 'string', Rule::in(['public', 'private', 'hidden'])],

            // Features & Limits
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'limits' => ['nullable', 'array'],
            'limits.max_users' => ['nullable', 'integer', 'min:0'],
            'limits.max_storage_gb' => ['nullable', 'integer', 'min:0'],
            'limits.max_employees' => ['nullable', 'integer', 'min:0'],
            'limits.max_projects' => ['nullable', 'integer', 'min:0'],
            'limits.max_api_calls' => ['nullable', 'integer', 'min:0'],
            'quotas' => ['nullable', 'array'],
            'quotas.*.key' => ['required_with:quotas', 'string', 'max:100'],
            'quotas.*.value' => ['nullable'],
            'quotas.*.unit' => ['nullable', 'string', 'max:50'],
            'quotas.*.metadata' => ['nullable', 'array'],

            // Legacy quota fields
            'max_users' => ['nullable', 'integer', 'min:0'],
            'max_storage_gb' => ['nullable', 'integer', 'min:0'],
            'duration_in_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'supports_custom_duration' => ['boolean'],

            // Stripe Integration
            'stripe_product_id' => ['nullable', 'string', 'max:255'],
            'stripe_monthly_price_id' => ['nullable', 'string', 'max:255'],
            'stripe_yearly_price_id' => ['nullable', 'string', 'max:255'],

            // Sorting
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Plan name is required.',
            'slug.required' => 'Plan slug is required for URL identification.',
            'slug.unique' => 'This slug is already in use by another plan.',
            'tier.required' => 'Please select a plan tier.',
            'tier.in' => 'Invalid tier. Must be free, starter, professional, or enterprise.',
            'monthly_price.required' => 'Monthly price is required.',
            'monthly_price.min' => 'Monthly price cannot be negative.',
            'visibility.in' => 'Visibility must be public, private, or hidden.',
            'currency.size' => 'Currency must be a 3-letter ISO code (e.g., USD, EUR).',
            'trial_days.max' => 'Trial period cannot exceed 365 days.',
            'module_codes.min' => 'At least one module must be selected for the plan.',
            'module_codes.*.in' => 'The selected module ":input" is not a valid installed module.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure boolean fields have proper values
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'is_featured' => $this->boolean('is_featured', false),
        ]);

        // Generate slug from name if not provided
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge([
                'slug' => Str::slug($this->input('name')),
            ]);
        }

        // Set default visibility if not provided
        if (! $this->filled('visibility')) {
            $this->merge(['visibility' => 'public']);
        }

        // Set default tier if not provided
        if (! $this->filled('tier')) {
            $this->merge(['tier' => 'starter']);
        }

        // Set default plan_type if not provided
        if (! $this->filled('plan_type')) {
            $this->merge(['plan_type' => 'paid']);
        }

        // Set default lifecycle policies
        if (! $this->filled('grace_days')) {
            $this->merge(['grace_days' => 7]);
        }
        if (! $this->filled('downgrade_policy')) {
            $this->merge(['downgrade_policy' => 'end_of_period']);
        }
        if (! $this->filled('cancellation_policy')) {
            $this->merge(['cancellation_policy' => 'end_of_period']);
        }
    }

    /**
     * Get available module codes from installed aero packages.
     *
     * @return array<string>
     */
    protected function getAvailableModuleCodes(): array
    {
        $codes = [];

        // Check vendor/aero/* packages
        $vendorPath = base_path('vendor/aero');
        if (File::exists($vendorPath)) {
            foreach (File::directories($vendorPath) as $packagePath) {
                $configPath = $packagePath.'/config/module.php';
                if (File::exists($configPath)) {
                    try {
                        $config = require $configPath;
                        if (isset($config['code']) && is_string($config['code'])) {
                            $codes[] = $config['code'];
                        }
                    } catch (\Throwable $e) {
                        // Skip invalid configs
                        continue;
                    }
                }
            }
        }

        // Also check packages/aero-* directory (for development)
        $packagesPath = base_path('packages');
        if (File::exists($packagesPath)) {
            foreach (File::directories($packagesPath) as $packagePath) {
                if (! str_contains(basename($packagePath), 'aero-')) {
                    continue;
                }
                $configPath = $packagePath.'/config/module.php';
                if (File::exists($configPath)) {
                    try {
                        $config = require $configPath;
                        if (isset($config['code']) && is_string($config['code'])) {
                            $codes[] = $config['code'];
                        }
                    } catch (\Throwable $e) {
                        // Skip invalid configs
                        continue;
                    }
                }
            }
        }

        return array_unique($codes);
    }
}
