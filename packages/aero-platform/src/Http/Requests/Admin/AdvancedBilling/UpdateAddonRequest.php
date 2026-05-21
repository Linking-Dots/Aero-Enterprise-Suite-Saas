<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\AdvancedBilling;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $addonId = $this->route('id');

        return [
            'code' => ['sometimes', 'string', 'max:64', 'alpha_dash', Rule::unique('platform_addons', 'code')->ignore($addonId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'billing_period' => ['sometimes', 'string', Rule::in(['monthly', 'quarterly', 'yearly', 'one_time'])],
        ];
    }
}
