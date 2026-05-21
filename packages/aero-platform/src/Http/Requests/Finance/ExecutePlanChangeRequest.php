<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecutePlanChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_target_id' => ['required', 'integer', 'min:1'],
            'billable_type' => ['required', Rule::in(['plan', 'product'])],
        ];
    }
}
