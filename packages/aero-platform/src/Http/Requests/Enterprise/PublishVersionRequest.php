<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:20', Rule::unique('release_versions', 'version')],
            'channel' => ['required', Rule::in(['stable', 'beta', 'lts'])],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
