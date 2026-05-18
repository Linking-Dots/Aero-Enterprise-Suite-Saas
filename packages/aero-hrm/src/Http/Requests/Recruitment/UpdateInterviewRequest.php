<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrmac', 'hrm.recruitment.interview-scheduling.update');
    }

    public function rules(): array
    {
        return [
            'application_id' => ['sometimes', 'integer', 'exists:job_applications,id'],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:15'],
            'type' => ['sometimes', 'string', 'in:phone,video,in_person,panel'],
            'location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'interviewer_ids' => ['sometimes', 'array'],
            'interviewer_ids.*' => ['integer', 'exists:employees,id'],
        ];
    }
}
