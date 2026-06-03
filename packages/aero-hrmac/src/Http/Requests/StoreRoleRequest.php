<?php

declare(strict_types=1);

namespace Aero\HRMAC\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // HRMAC middleware handles authorization
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_dashboard' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'scope' => ['nullable', 'string', 'max:50'],
        ];
    }
}
