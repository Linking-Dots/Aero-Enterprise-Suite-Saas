<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GDPR Right-to-be-Forgotten Form Request (Audit D7).
 *
 * Requires an explicit reason (audit-trail obligation) and a boolean
 * confirmation field so callers cannot accidentally trigger the purge.
 */
class TenantForgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.tenants.tenant-list.forget') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'confirm' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required for GDPR forget — this is an audit-trail requirement.',
            'reason.min' => 'Reason must be at least 10 characters.',
            'confirm.accepted' => 'You must explicitly confirm this destructive action.',
        ];
    }
}
