<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prefix' => ['sometimes', 'string', 'max:10', 'regex:/^[A-Z0-9\-]+$/'],
            'digit_padding' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'active_template_id' => ['sometimes', 'nullable', 'integer', 'exists:invoice_templates,id'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:200'],
            'footer_text' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
