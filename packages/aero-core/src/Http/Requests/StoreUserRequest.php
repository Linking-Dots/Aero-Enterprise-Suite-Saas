<?php

namespace Aero\Core\Http\Requests;

use Aero\Core\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'attendance_type_id' => ['nullable', 'exists:attendance_types,id'],
            'date_of_joining' => ['nullable', 'date'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:500'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'single_device_login_enabled' => ['nullable'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'report_to' => ['nullable', 'exists:users,id'],
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
            'name.required' => 'Full name is required.',
            'name.max' => 'Full name cannot exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.unique' => 'This phone number is already in use.',
            'employee_id.unique' => 'This employee ID already exists.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'birthday.before' => 'Birthday must be a date in the past.',
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
        ];
    }
}
