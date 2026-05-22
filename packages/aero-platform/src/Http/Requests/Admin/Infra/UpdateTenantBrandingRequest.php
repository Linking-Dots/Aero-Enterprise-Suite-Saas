<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\Infra;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantBrandingRequest extends FormRequest
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
            'logo' => ['nullable', 'image', 'max:2048', 'mimes:png,jpg,jpeg,svg'],
            'favicon' => ['nullable', 'image', 'max:512', 'mimes:png,ico'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'email_from_name' => ['nullable', 'string', 'max:100'],
            'email_from_address' => ['nullable', 'email', 'max:255'],
        ];
    }
}
