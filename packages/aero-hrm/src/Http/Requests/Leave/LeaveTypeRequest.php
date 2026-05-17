<?php

namespace Aero\HRM\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.leaves.leave-types.edit') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('type')?->id;

        return [
            'name'              => ['required', 'string', 'max:80', Rule::unique('leave_types', 'name')->ignore($id)],
            'code'              => ['required', 'string', 'max:16', Rule::unique('leave_types', 'code')->ignore($id)],
            'color'             => ['nullable', 'string', 'max:16'],
            'days_per_year'     => ['required', 'numeric', 'min:0', 'max:366'],
            'is_paid'           => ['boolean'],
            'requires_approval' => ['boolean'],
            'carry_forward'     => ['boolean'],
            'encashable'        => ['boolean'],
            'max_carry_forward' => ['nullable', 'numeric', 'min:0'],
            'is_active'         => ['boolean'],
        ];
    }
}
