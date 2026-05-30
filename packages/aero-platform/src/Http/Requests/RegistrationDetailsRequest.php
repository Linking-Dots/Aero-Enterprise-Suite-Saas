<?php

namespace Aero\Platform\Http\Requests;

use Aero\Platform\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Email and subdomain uniqueness is validated against non-pending tenants only.
     * This allows users to resume incomplete registrations with the same credentials.
     * Pending/failed tenants can be "taken over" by the new registration attempt.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            // Fix #16: Validate owner/contact fields that are submitted from the registration wizard.
            'owner_name' => ['nullable', 'string', 'max:150'],
            'owner_email' => ['nullable', 'email:filter', 'max:150'],
            'owner_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'industry' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'email:filter',
                'max:150',
                Rule::unique('tenants', 'email')
                    ->where(function ($query) {
                        // Only block if there's an active/provisioning/suspended tenant
                        // Allow pending/failed tenants to be resumed
                        $query->whereNotIn('status', [
                            Tenant::STATUS_PENDING,
                            Tenant::STATUS_FAILED,
                        ]);
                    }),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'subdomain' => [
                'required',
                'string',
                // Axis A A7 — single source of truth for length (shared with the
                // availability probe) so the probe and the register call agree.
                'min:'.config('tenancy.subdomain.min_length', 3),
                'max:'.config('tenancy.subdomain.max_length', 63),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                // Plan 03 T6 — block platform-infrastructure subdomains
                Rule::notIn(config('tenancy.reserved_subdomains', [])),
                Rule::unique('tenants', 'subdomain')
                    ->where(function ($query) {
                        // Only block if there's an active/provisioning/suspended tenant
                        // Allow pending/failed tenants to be resumed
                        $query->whereNotIn('status', [
                            Tenant::STATUS_PENDING,
                            Tenant::STATUS_FAILED,
                        ]);
                    }),
            ],
            'team_size' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'subdomain.regex' => 'Subdomain may only contain lowercase letters, numbers, and single hyphens.',
            'subdomain.not_in' => 'That subdomain is reserved by the platform. Please choose a different one.',
            'subdomain.min' => 'Subdomain must be at least 3 characters.',
            // Fix #23: Use a single generic message for both email and subdomain uniqueness failures
            // to prevent user-enumeration (distinguishing which value is taken).
            'email.unique' => 'This email or subdomain is already in use. Please choose different values.',
            'subdomain.unique' => 'This email or subdomain is already in use. Please choose different values.',
            'phone.regex' => 'Please enter a valid phone number in international format (e.g., +1234567890).',
            'owner_phone.regex' => 'Please enter a valid phone number in international format (e.g., +1234567890).',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('subdomain')) {
            $this->merge([
                'subdomain' => strtolower((string) $this->input('subdomain')),
            ]);
        }
    }
}
