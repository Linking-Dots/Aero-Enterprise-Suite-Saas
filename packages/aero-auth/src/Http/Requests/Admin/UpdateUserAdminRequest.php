<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Requests\Admin;

use Aero\Auth\Http\Concerns\ResolvesContextUserModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Context-free "update identity user" request — see StoreUserAdminRequest
 * doc-block for why Employee/HRM-domain fields from the legacy
 * Aero\Core\Http\Requests\UpdateUserRequest were dropped.
 */
class UpdateUserAdminRequest extends FormRequest
{
    use ResolvesContextUserModel;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $table = (new ($this->resolveUserModel()))->getTable();
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique($table, 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique($table, 'phone')->ignore($userId)],
            'user_name' => ['nullable', 'string', 'max:255', Rule::unique($table, 'user_name')->ignore($userId)],
            'timezone' => ['nullable', 'string', 'max:64'],
            'single_device_login_enabled' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.unique' => 'This phone number is already in use.',
            'user_name.unique' => 'This username is already taken.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role_ids.array' => 'Roles must be provided as an array.',
            'role_ids.*.exists' => 'One or more selected roles do not exist.',
        ];
    }
}
