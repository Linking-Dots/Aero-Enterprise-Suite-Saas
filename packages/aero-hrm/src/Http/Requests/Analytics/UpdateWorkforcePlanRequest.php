<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateWorkforcePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hrmac', 'hrm.workforce-planning.workforce-plans.update');
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'plans' => ['required', 'array', 'min:1'],
            'plans.*.fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'plans.*.department_id' => ['required', 'integer', 'exists:departments,id'],
            'plans.*.target_headcount' => ['required', 'integer', 'min:0'],
            'plans.*.target_hires' => ['sometimes', 'integer', 'min:0'],
            'plans.*.target_attrition' => ['sometimes', 'integer', 'min:0'],
            'plans.*.notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
