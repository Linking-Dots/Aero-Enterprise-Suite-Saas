<?php

namespace Aero\HRM\Http\Requests\Safety;

use Illuminate\Foundation\Http\FormRequest;

class AssignTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'training_id' => ['required', 'integer', 'exists:hrm_safety_trainings,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ];
    }
}
