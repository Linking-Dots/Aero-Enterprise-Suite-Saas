<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:8'],
            'exchange_rate_to_usd' => ['sometimes', 'numeric', 'min:0.00000001'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
