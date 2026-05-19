<?php

namespace Aero\HRM\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'action_type_id' => ['nullable', 'integer', 'exists:hrm_disciplinary_action_types,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }
}
