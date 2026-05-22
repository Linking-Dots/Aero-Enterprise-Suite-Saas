<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRateLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requests_per_minute' => ['required', 'integer', 'min:1', 'max:100000'],
            'requests_per_day' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
