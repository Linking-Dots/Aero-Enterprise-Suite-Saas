<?php

namespace Aero\HRM\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class RecordExitInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'responses' => ['required', 'array'],
            'summary' => ['nullable', 'string'],
            'eligible_for_rehire' => ['nullable', 'integer', 'between:0,1'],
        ];
    }
}
