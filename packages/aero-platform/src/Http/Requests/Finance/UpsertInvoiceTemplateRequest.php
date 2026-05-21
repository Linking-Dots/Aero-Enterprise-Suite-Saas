<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpsertInvoiceTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['sometimes', 'integer', 'exists:invoice_templates,id'],
            'name' => ['required', 'string', 'max:100'],
            'html_body' => ['required', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
