<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\Infra;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureDkimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'max:255'],
            'dkim_selector' => ['required', 'string', 'max:63', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'dkim_private_key' => ['required', 'string', 'min:100'],
            'email_from_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email_from_address' => ['sometimes', 'nullable', 'email', 'max:190'],
        ];
    }
}
