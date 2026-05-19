<?php

namespace Aero\HRM\Http\Requests\Safety;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:induction,refresher,equipment,emergency'],
            'duration_minutes' => ['integer', 'min:1'],
            'mandatory' => ['boolean'],
            'active' => ['boolean'],
        ];
    }
}
