<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Requests\Admin;

use Aero\Auth\Http\Concerns\ResolvesContextUserModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Context-free "create identity user" request — used by
 * Aero\Auth\Http\Controllers\Admin\UserAdminController in BOTH the tenant
 * (`web` guard, `users` table) and platform (`landlord` guard) contexts.
 *
 * Deliberately scoped to identity-only fields (name/email/phone/password/
 * role_ids). The legacy Aero\Core\Http\Requests\StoreUserRequest also
 * validated Employee/HRM-domain fields (department_id, designation_id,
 * attendance_type_id, salary_amount, nid, passport_no, nationality,
 * religion, marital_status, report_to, birthday, gender, address, about) —
 * those are dropped here because Aero\Auth\Services\UserService::create()
 * never persists them (it only reads name/user_name/email/password/
 * timezone/role_ids), and validating `exists:departments,id` etc. against
 * tables that don't exist in the platform context would be a latent 500.
 * See the Backend Output Report for this task for the explicit call-out.
 */
class StoreUserAdminRequest extends FormRequest
{
    use ResolvesContextUserModel;

    /**
     * Authorization for this shared surface is enforced by the route's
     * `hrmac:` middleware (see docs/standards/hrmac-convention.md), not a
     * fixed Policy — aero-auth cannot assume a specific Policy/Model pairing
     * is registered in every consuming context.
     */
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

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique($table, 'email')],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique($table, 'phone')],
            'user_name' => ['nullable', 'string', 'max:255', Rule::unique($table, 'user_name')],
            'timezone' => ['nullable', 'string', 'max:64'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'single_device_login_enabled' => ['nullable', 'boolean'],
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
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.unique' => 'This phone number is already in use.',
            'user_name.unique' => 'This username is already taken.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role_ids.array' => 'Roles must be provided as an array.',
            'role_ids.*.exists' => 'One or more selected roles do not exist.',
        ];
    }
}
