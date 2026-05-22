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
            'dkim_selector' => ['required', 'string', 'max:63', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'dkim_private_key' => ['required', 'string', 'min:100'],
        ];
    }
}
