<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrmac', 'hrm.recruitment.interview-scheduling.create');
    }

    public function rules(): array
    {
        return [
            'application_id' => ['required', 'integer', 'exists:job_applications,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15'],
            'type' => ['required', 'string', 'in:phone,video,in_person,panel'],
            'location' => ['nullable', 'string', 'max:500'],
            'interviewer_ids' => ['required', 'array'],
            'interviewer_ids.*' => ['integer', 'exists:employees,id'],
        ];
    }
}
