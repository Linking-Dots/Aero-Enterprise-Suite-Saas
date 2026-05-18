<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class MoveStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrmac', 'hrm.recruitment.applicants.update');
    }

    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'integer', 'exists:job_hiring_stages,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
