<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\Infra;

use Illuminate\Foundation\Http\FormRequest;

class AddIpAllowlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ip_cidr' => [
                'required',
                'string',
                'max:43',
                // Valid IPv4 or IPv4 CIDR
                'regex:/^(\d{1,3}\.){3}\d{1,3}(\/([0-9]|[1-2][0-9]|3[0-2]))?$/',
            ],
            'label' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
