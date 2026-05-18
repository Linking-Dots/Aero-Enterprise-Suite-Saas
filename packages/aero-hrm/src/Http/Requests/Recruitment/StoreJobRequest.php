<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrmac', 'hrm.recruitment.job-openings.create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string'],
            'positions' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,open'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'posting_date' => ['nullable', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:posting_date'],
        ];
    }
}
