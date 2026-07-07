<?php

declare(strict_types=1);

namespace Aero\Assistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
            'context' => ['nullable', 'array'],
            'context.page' => ['nullable', 'string', 'max:255'],
        ];
    }
}
