<?php

declare(strict_types=1);

namespace Aero\Core\Http\Requests;

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
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
