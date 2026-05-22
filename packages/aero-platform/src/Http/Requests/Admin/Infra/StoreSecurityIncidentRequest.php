<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\Infra;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecurityIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'severity' => ['required', 'string', 'in:low,medium,high,critical'],
            'description' => ['required', 'string', 'max:5000'],
            'status' => ['nullable', 'string', 'in:open,investigating,resolved'],
            'affected_tenants' => ['nullable', 'array'],
            'affected_tenants.*' => ['string'],
        ];
    }
}
