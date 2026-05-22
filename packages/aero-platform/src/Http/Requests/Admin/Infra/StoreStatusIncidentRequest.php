<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\Infra;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatusIncidentRequest extends FormRequest
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
            'status' => ['required', 'string', 'in:investigating,identified,monitoring,resolved'],
            'impact' => ['required', 'string', 'in:minor,major,critical'],
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['integer', 'exists:central.status_page_components,id'],
            'started_at' => ['nullable', 'date'],
            'update_message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
