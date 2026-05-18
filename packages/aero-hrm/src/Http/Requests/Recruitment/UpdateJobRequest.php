<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrmac', 'hrm.recruitment.job-openings.update');
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:50'],
            'description' => ['sometimes', 'string'],
            'positions' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:draft,open,closed'],
            'salary_currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'salary_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'salary_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'posting_date' => ['sometimes', 'nullable', 'date'],
            'closing_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
