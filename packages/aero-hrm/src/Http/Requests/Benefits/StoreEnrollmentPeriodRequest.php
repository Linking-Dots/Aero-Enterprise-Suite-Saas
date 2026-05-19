<?php

namespace Aero\HRM\Http\Requests\Benefits;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'coverage_starts_at' => ['required', 'date'],
            'coverage_ends_at' => ['required', 'date', 'after:coverage_starts_at'],
            'audience_filter' => ['nullable', 'array'],
            'benefit_ids' => ['required', 'array', 'min:1'],
            'benefit_ids.*' => ['integer', 'exists:hrm_benefits,id'],
        ];
    }
}
