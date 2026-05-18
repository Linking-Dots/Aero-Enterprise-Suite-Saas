<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreOnboardingRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrmac', 'hrm.onboarding.onboarding-list.create');
    }

    public function rules(): array
    {
        return [
            'checklist' => ['nullable', 'array'],
            'employment_type' => ['required', 'string', 'in:full_time,part_time,contract,intern'],
            'start_date' => ['required', 'date'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
        ];
    }
}
