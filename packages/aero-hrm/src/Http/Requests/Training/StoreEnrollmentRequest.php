<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hrmac', 'hrm.training.enrollment.manage');
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:training_sessions,id'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'source' => ['nullable', 'string', 'in:admin,self'],
        ];
    }
}
