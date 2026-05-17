<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization enforced by hrmac:hrm.employees.detail.edit route middleware.
        return true;
    }

    public function rules(): array
    {
        $bound = $this->route('employee');
        $employeeId = ($bound instanceof \Aero\HRM\Models\Employee) ? $bound->id : $bound;

        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
            'employee_code' => ['required', 'string', 'max:32', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'date_of_joining' => ['required', 'date'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'status' => ['required', Rule::in(['active', 'probation', 'on_leave', 'terminated', 'resigned'])],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'work_location' => ['nullable', 'string', 'max:120'],
            'shift' => ['nullable', 'string', 'max:120'],
            'passport_no' => ['nullable', 'string', 'max:64'],
            'visa_no' => ['nullable', 'string', 'max:64'],
            'emirates_id' => ['nullable', 'string', 'max:32'],
            'national_id' => ['nullable', 'string', 'max:32'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
