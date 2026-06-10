<?php

namespace Aero\Auth\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * AcceptTeamInvitationRequest
 *
 * Validates the request for accepting a team invitation and creating an account.
 */
class AcceptTeamInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Anyone with a valid token can accept an invitation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'terms' => [
                'accepted',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your full name.',
            'name.min' => 'Your name must be at least 2 characters.',
            'password.required' => 'Please create a password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'terms.accepted' => 'You must accept the terms of service.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
            'terms' => 'terms of service',
        ];
    }
}
