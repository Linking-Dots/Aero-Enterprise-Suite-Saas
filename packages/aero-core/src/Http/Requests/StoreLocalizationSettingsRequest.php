<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocalizationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'timezone' => ['nullable', 'string', 'max:100', 'timezone'],
            'currency' => ['nullable', 'string', 'max:3'],
            'locale' => ['nullable', 'string', 'max:10'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'time_format' => ['nullable', 'string', 'in:12,24'],
            'first_day_of_week' => ['nullable', 'integer', 'between:0,6'],
        ];
    }

    public function attributes(): array
    {
        return [
            'timezone' => 'Timezone',
            'currency' => 'Currency',
            'locale' => 'Locale',
            'date_format' => 'Date Format',
            'time_format' => 'Time Format',
            'first_day_of_week' => 'First Day of Week',
        ];
    }
}
