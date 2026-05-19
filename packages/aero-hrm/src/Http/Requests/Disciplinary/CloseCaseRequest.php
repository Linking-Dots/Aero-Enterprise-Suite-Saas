<?php

namespace Aero\HRM\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::in(['none', 'verbal', 'written', 'pip', 'suspension', 'termination'])],
            'closure_notes' => ['nullable', 'string'],
        ];
    }
}
