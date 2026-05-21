<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email:rfc,dns', 'max:200', Rule::unique('reseller_partners', 'email')],
            'commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'portal_slug' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('reseller_partners', 'portal_slug')],
        ];
    }
}
