<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

class StoreRunbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*' => ['string'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
