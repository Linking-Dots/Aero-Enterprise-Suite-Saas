<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\AdvancedBilling;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayAsYouGoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'billing_threshold' => ['required', 'numeric', 'min:0'],
            'auto_invoice' => ['required', 'boolean'],
        ];
    }
}
