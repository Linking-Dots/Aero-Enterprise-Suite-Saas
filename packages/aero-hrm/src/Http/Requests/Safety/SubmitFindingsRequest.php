<?php

namespace Aero\HRM\Http\Requests\Safety;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFindingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conducted_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'findings' => ['required', 'array', 'min:1'],
            'findings.*.category' => ['required', 'string', 'max:255'],
            'findings.*.description' => ['required', 'string'],
            'findings.*.severity' => ['required', 'string', 'in:low,medium,high,critical'],
            'findings.*.due_date' => ['nullable', 'date'],
        ];
    }
}
