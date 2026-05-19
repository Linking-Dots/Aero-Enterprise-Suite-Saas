<?php

namespace Aero\HRM\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class StoreExitInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'scheduled_for' => ['required', 'date'],
            'interviewer_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
