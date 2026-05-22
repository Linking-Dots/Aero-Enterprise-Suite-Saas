<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\Infra;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatusComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:operational,degraded,partial_outage,major_outage'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
