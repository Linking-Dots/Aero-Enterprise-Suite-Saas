<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCancellationFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => ['sometimes', 'array'],
            'plan.survey' => ['sometimes', 'array'],
            'plan.offers' => ['sometimes', 'array'],
            'product' => ['sometimes', 'array'],
            'product.survey' => ['sometimes', 'array'],
            'product.offers' => ['sometimes', 'array'],
        ];
    }
}
