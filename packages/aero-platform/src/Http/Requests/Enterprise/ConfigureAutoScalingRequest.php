<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureAutoScalingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resource_type' => ['required', 'string', 'max:50'],
            'metric' => ['required', 'string', 'max:100'],
            'scale_up_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'scale_down_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_instances' => ['required', 'integer', 'min:1'],
            'max_instances' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_enabled' => ['boolean'],
        ];
    }
}
