<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class GenerateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_codes' => ['required', 'array', 'min:1'],
            'product_codes.*' => ['required', 'string', 'max:50'],
            'plan_code' => ['nullable', 'string', 'max:100'],
            'issued_to_name' => ['required', 'string', 'max:255'],
            'issued_to_email' => ['required', 'email', 'max:255'],
            'max_activations' => ['required', 'integer', 'min:1', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
