<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partnerId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'email' => ['sometimes', 'email:rfc', 'max:200',
                Rule::unique('reseller_partners', 'email')->ignore($partnerId)],
            'commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'portal_slug' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('reseller_partners', 'portal_slug')->ignore($partnerId)],
        ];
    }
}
