<?php

namespace Aero\HRM\Http\Requests\Safety;

use Illuminate\Foundation\Http\FormRequest;

class InvestigateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'root_cause' => ['required', 'string', 'max:2000'],
            'corrective_action' => ['required', 'string', 'max:2000'],
        ];
    }
}
