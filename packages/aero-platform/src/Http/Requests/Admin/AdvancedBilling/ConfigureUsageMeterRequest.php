<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\AdvancedBilling;

use Aero\Platform\Models\UsageMeter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigureUsageMeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'event_code' => ['sometimes', 'string', 'max:64'],
            'aggregation' => ['sometimes', Rule::in([UsageMeter::AGGREGATION_SUM, UsageMeter::AGGREGATION_COUNT, UsageMeter::AGGREGATION_MAX])],
            'price_per_unit' => ['sometimes', 'numeric', 'min:0'],
            'unit_label' => ['sometimes', 'string', 'max:32'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
