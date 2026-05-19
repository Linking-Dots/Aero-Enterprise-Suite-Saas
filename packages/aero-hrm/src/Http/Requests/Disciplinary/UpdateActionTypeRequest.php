<?php

namespace Aero\HRM\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeId = $this->route('type')?->id ?? $this->route('type');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('hrm_disciplinary_action_types', 'name')->ignore($typeId)],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'description' => ['nullable', 'string'],
            'escalates_after_count' => ['nullable', 'integer', 'min:1'],
            'escalates_to_type' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
        ];
    }
}
