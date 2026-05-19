<?php

namespace Aero\HRM\Http\Requests\Benefits;

use Illuminate\Foundation\Http\FormRequest;

class EnrollBenefitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'integer', 'exists:hrm_benefit_enrollment_periods,id'],
            'elections' => ['required', 'array'],
            'elections.*.benefit_id' => ['required', 'integer', 'exists:hrm_benefits,id'],
            'elections.*.status' => ['required', 'in:enrolled,waived'],
            'elections.*.dependents_count' => ['integer', 'min:0', 'max:20'],
            'elections.*.waiver_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
