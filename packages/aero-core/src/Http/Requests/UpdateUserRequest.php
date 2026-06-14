<?php

namespace Aero\Core\Http\Requests;

use Aero\Core\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = User::findOrFail($this->route('id'));

        return $this->user()->can('update', $user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($userId)],
            'employee_id' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($userId)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'attendance_type_id' => ['nullable', 'exists:attendance_types,id'],
            'date_of_joining' => ['nullable', 'date'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:500'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'report_to' => ['nullable', 'exists:users,id'],
            'single_device_login_enabled' => ['nullable'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'about' => ['nullable', 'string', 'max:1000'],
            'nid' => ['nullable', 'string', 'max:50'],
            'passport_no' => ['nullable', 'string', 'max:50'],
            'passport_exp_date' => ['nullable', 'date', 'after:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Full name cannot exceed 255 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.unique' => 'This phone number is already in use.',
            'employee_id.unique' => 'This employee ID already exists.',
            'birthday.before' => 'Birthday must be a date in the past.',
            'passport_exp_date.after' => 'Passport expiration date must be in the future.',
            'password.confirmed' => 'The password confirmation does not match.',
            'department_id.exists' => 'The selected department does not exist.',
            'designation_id.exists' => 'The selected designation does not exist.',
            'report_to.exists' => 'The selected reporting manager does not exist.',
            'role_ids.array' => 'Roles must be provided as an array.',
            'role_ids.*.exists' => 'One or more selected roles do not exist.',
            'profile_image.image' => 'Profile picture must be an image file.',
            'profile_image.mimes' => 'Profile picture must be in JPEG, JPG, or PNG format.',
            'profile_image.max' => 'Profile picture size cannot exceed 2MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'department_id' => 'department',
            'designation_id' => 'designation',
            'attendance_type_id' => 'attendance type',
            'date_of_joining' => 'joining date',
            'salary_amount' => 'salary',
            'report_to' => 'reporting manager',
            'nid' => 'national ID',
        ];
    }
}
