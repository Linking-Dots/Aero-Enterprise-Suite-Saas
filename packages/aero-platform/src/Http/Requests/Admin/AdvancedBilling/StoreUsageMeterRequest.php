<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\AdvancedBilling;

use Aero\Platform\Models\UsageMeter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsageMeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('usage_meters', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'event_code' => ['required', 'string', 'max:64'],
            'aggregation' => ['required', Rule::in([UsageMeter::AGGREGATION_SUM, UsageMeter::AGGREGATION_COUNT, UsageMeter::AGGREGATION_MAX])],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'unit_label' => ['sometimes', 'string', 'max:32'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
