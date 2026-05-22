<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class SetRtoRpoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service' => ['required', 'string', 'max:100'],
            'rto_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'rpo_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
        ];
    }
}
