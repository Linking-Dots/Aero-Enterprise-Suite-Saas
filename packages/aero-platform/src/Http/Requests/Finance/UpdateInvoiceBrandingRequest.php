<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => ['sometimes', 'file', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:500'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:200'],
            'footer_text' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
