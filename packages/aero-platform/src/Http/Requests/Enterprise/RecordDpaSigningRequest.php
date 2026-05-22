<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class RecordDpaSigningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string'],
            'template_id' => ['required', 'string', 'exists:dpa_templates,id'],
            'signed_by_name' => ['required', 'string', 'max:255'],
            'signed_by_email' => ['required', 'email', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
        ];
    }
}
