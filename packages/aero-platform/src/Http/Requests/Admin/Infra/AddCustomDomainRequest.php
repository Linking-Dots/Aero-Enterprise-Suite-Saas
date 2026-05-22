<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\Infra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCustomDomainRequest extends FormRequest
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
            'domain' => [
                'required',
                'string',
                'max:253',
                'regex:/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.[A-Za-z0-9-]{1,63})*\.[A-Za-z]{2,}$/',
                Rule::unique('central.tenant_custom_domains', 'domain')->whereNull('deleted_at'),
            ],
        ];
    }
}
