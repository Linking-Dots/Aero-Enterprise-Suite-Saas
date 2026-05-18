<?php

namespace Aero\HRM\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.leaves.leave-requests.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id'   => ['required', 'integer', 'exists:employees,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
