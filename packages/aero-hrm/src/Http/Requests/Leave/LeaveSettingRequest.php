<?php

namespace Aero\HRM\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.leaves.leave-policies.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'accrual_cycle'           => ['required', Rule::in(['yearly', 'monthly', 'quarterly'])],
            'allow_negative_balance'  => ['boolean'],
            'auto_approve_under_days' => ['boolean'],
            'auto_approve_threshold'  => ['nullable', 'numeric', 'min:0', 'max:30'],
            'encashment_enabled'      => ['boolean'],
        ];
    }
}
