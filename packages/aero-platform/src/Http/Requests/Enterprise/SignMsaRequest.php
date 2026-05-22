<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class SignMsaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signed_by_name' => ['required', 'string', 'max:255'],
            'signed_by_email' => ['required', 'email', 'max:255'],
        ];
    }
}
