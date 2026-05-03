<?php

namespace Aero\Platform\Http\Requests;

use Aero\Platform\Models\Module;
use Aero\Platform\Models\Plan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            // Fix #22: Restrict to active plans only (inactive/archived plans must not be selectable).
            // Plan selection is now REQUIRED (not optional)
            'plan_id' => ['required', 'string', Rule::exists('plans', 'id')->where('is_active', true)],
            // Module selection is now REQUIRED with at least one product (core is auto-included)
            'modules' => ['required', 'array', 'min:1'],
            // Module codes are alphanumeric identifiers (e.g. 'hrm', 'crm').
            // Per-plan module whitelisting is enforced in withValidator() below.
            // We intentionally do not restrict to a DB-driven list here because
            // discovered modules come from Composer packages and are not necessarily
            // registered in any plan — the controller handles final filtering.
            'modules.*' => ['string', 'max:100', 'regex:/^[a-z0-9_-]+$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $planId = $this->input('plan_id');
            $modules = $this->input('modules', []);

            // Both plan and at least one module are now required
            if (empty($planId)) {
                $validator->errors()->add('plan_id', 'Please select a plan to continue.');
            }

            if (empty($modules)) {
                $validator->errors()->add('modules', 'Please select at least one product to continue.');
            }

            // Validate selected modules against module_pricing table (not central modules table,
            // which in SaaS mode only contains 'platform'). module_pricing holds all sellable products.
            if (! empty($modules)) {
                $allowed = \DB::table('module_pricing')->where('is_active', true)->pluck('module_code')->all();
                $allowed = array_values(array_filter($allowed));

                // If user requested modules not available, reject
                $invalid = array_diff($modules, $allowed);
                if (! empty($invalid)) {
                    $validator->errors()->add('modules', 'Selected modules are not available.');
                }

                // Sanitize modules to only include allowed ones
                $cleanModules = array_values(array_intersect($modules, $allowed));
                $this->merge(['modules' => $cleanModules]);
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // Ensure modules is always an array
        if (! $this->has('modules') || ! is_array($this->input('modules'))) {
            $this->merge(['modules' => []]);
        }
    }
}
