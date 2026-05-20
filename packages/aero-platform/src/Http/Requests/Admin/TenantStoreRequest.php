<?php

namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TenantStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'plan_id' => 'nullable|integer|exists:plans,id',
            'product_id' => 'nullable|uuid|exists:products,id',
            'billing_cycle' => 'nullable|in:monthly,yearly',
            'byoc_enabled' => 'boolean',
            'timezone' => 'nullable|string|max:64',
        ];
    }
}
