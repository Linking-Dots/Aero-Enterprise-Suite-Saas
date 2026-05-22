<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\Infra;

use Illuminate\Foundation\Http\FormRequest;

class StoreBackupScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', 'string', 'in:daily,weekly,monthly'],
            'time_of_day' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'storage_backend_code' => ['required', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ];
    }
}
