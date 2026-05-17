<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests;

use Aero\HRM\Models\Grade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bound = $this->route('grade');
        $id = ($bound instanceof Grade) ? $bound->id : $bound;

        return [
            'name' => ['required', 'string', 'max:64', Rule::unique('grades', 'name')->ignore($id)],
            'code' => ['nullable', 'string', 'max:16'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
