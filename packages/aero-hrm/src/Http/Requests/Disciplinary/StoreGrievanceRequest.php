<?php

namespace Aero\HRM\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGrievanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['harassment', 'discrimination', 'workplace_safety', 'policy_violation', 'interpersonal', 'other'])],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'confidentiality' => ['required', Rule::in(['standard', 'confidential', 'anonymous'])],
            'against_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
