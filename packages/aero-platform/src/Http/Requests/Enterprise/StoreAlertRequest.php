<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'metric' => ['required', 'string', 'max:100'],
            'condition' => ['required', Rule::in(['>', '<', '>=', '<=', '=='])],
            'threshold' => ['required', 'numeric'],
            'severity' => ['required', Rule::in(['info', 'warning', 'critical'])],
            'is_active' => ['boolean'],
            'channels' => ['nullable', 'array'],
        ];
    }
}
