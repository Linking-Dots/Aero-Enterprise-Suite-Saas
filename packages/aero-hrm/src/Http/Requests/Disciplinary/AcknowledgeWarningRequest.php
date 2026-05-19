<?php

namespace Aero\HRM\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response' => ['nullable', 'string'],
        ];
    }
}
