<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishTosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:20', Rule::unique('tos_versions', 'version')],
            'content_md' => ['required', 'string'],
            'requires_re_acceptance' => ['boolean'],
        ];
    }
}
