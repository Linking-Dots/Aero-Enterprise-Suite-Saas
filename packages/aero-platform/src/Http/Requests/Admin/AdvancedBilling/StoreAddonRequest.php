<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\AdvancedBilling;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('platform_addons', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'string', Rule::in(['monthly', 'quarterly', 'yearly', 'one_time'])],
        ];
    }
}
