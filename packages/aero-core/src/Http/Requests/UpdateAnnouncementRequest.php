<?php

declare(strict_types=1);

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'type' => ['sometimes', 'required', 'in:info,warning,success,danger'],
            'status' => ['sometimes', 'required', 'in:draft,published,archived'],
            'audience' => ['sometimes', 'required', 'in:all,admins,employees'],
            'is_pinned' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
