<?php

namespace Aero\HRM\Http\Requests\Benefits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBenefitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('hrm_benefits', 'code')->ignore($this->route('benefit'))],
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'in:health,dental,vision,life,disability,pension,wellness,other'],
            'description' => ['nullable', 'string'],
            'provider' => ['nullable', 'string', 'max:255'],
            'employee_cost' => ['sometimes', 'numeric', 'min:0'],
            'employer_cost' => ['sometimes', 'numeric', 'min:0'],
            'frequency' => ['sometimes', 'in:monthly,biweekly,weekly,annual'],
            'allows_dependents' => ['boolean'],
            'dependent_cost' => ['nullable', 'numeric', 'min:0'],
            'eligibility_rules' => ['nullable', 'array'],
            'active' => ['boolean'],
        ];
    }
}
