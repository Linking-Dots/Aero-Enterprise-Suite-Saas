<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'config' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
