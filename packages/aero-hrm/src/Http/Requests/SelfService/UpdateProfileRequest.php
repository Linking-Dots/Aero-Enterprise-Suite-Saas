<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\SelfService;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact' => ['sometimes', 'array'],
            'contact.personal_email' => ['nullable', 'email', 'max:255'],
            'contact.personal_phone' => ['nullable', 'string', 'max:30'],
            'contact.address_line1' => ['nullable', 'string', 'max:255'],
            'contact.city' => ['nullable', 'string', 'max:100'],
            'contact.country' => ['nullable', 'string', 'max:100'],
            'emergency_contact' => ['sometimes', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:150'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
