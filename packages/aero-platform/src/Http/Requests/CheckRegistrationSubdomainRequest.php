<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckRegistrationSubdomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Plan 03 T6 — same shape as RegistrationDetailsRequest so the
            // pre-flight availability probe and the actual register call
            // can't disagree on what's allowed.
            'subdomain' => [
                'required',
                'string',
                // Axis A A7 — shared length bounds (same as RegistrationDetailsRequest)
                // so the availability probe can't accept what register later rejects.
                'min:'.config('tenancy.subdomain.min_length', 3),
                'max:'.config('tenancy.subdomain.max_length', 63),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(config('tenancy.reserved_subdomains', [])),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subdomain.not_in' => 'That subdomain is reserved by the platform. Please choose a different one.',
            'subdomain.regex'  => 'Subdomain may only contain lowercase letters, numbers, and single hyphens.',
            'subdomain.min'    => 'Subdomain must be at least 3 characters.',
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
