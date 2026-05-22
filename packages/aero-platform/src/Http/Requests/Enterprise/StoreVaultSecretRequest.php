<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVaultSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('vault_secrets', 'name')],
            'value' => ['required', 'string'],
            'scope' => ['required', Rule::in(['platform', 'tenant', 'integration'])],
            'tenant_id' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
