<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string'],
            'plan_id' => ['nullable', 'string', 'exists:plans,id'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['string', 'exists:products,id'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'msa_id' => ['nullable', 'string', 'exists:master_service_agreements,id'],
        ];
    }
}
