<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests;

use Aero\HRM\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bound = $this->route('department');
        $id = ($bound instanceof Department) ? $bound->id : $bound;

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('departments', 'name')->ignore($id)],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'head_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
