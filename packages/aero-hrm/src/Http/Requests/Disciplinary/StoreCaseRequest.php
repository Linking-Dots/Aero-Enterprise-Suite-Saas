<?php

namespace Aero\HRM\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'action_type_id' => ['required', 'integer', 'exists:hrm_disciplinary_action_types,id'],
            'incident_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }
}
