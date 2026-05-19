<?php

namespace Aero\HRM\Http\Requests\Benefits;

use Illuminate\Foundation\Http\FormRequest;

class StoreBenefitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:hrm_benefits,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:health,dental,vision,life,disability,pension,wellness,other'],
            'description' => ['nullable', 'string'],
            'provider' => ['nullable', 'string', 'max:255'],
            'employee_cost' => ['required', 'numeric', 'min:0'],
            'employer_cost' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'in:monthly,biweekly,weekly,annual'],
            'allows_dependents' => ['boolean'],
            'dependent_cost' => ['nullable', 'numeric', 'min:0'],
            'eligibility_rules' => ['nullable', 'array'],
            'active' => ['boolean'],
        ];
    }
}
