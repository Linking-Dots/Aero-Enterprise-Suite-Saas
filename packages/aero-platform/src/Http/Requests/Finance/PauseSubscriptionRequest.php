<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PauseSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'billable_type' => ['required', Rule::in(['plan', 'product'])],
            'resume_at' => ['sometimes', 'nullable', 'date', 'after:today'],
        ];
    }
}
