<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests;

use Aero\HRM\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bound = $this->route('designation');
        $id = ($bound instanceof Designation) ? $bound->id : $bound;

        return [
            'title' => ['required', 'string', 'max:120', Rule::unique('designations', 'title')->ignore($id)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
