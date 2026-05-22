<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureCdnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'string', 'max:50'],
            'endpoint_url' => ['nullable', 'url', 'max:500'],
            'purge_enabled' => ['boolean'],
        ];
    }
}
