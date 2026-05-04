<?php

declare(strict_types=1);

namespace Aero\Core\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TagRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tagId = $this->route('tag')?->id ?? $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tag name is required.',
            'name.max' => 'Tag name cannot exceed 50 characters.',
            'color.regex' => 'Color must be a valid hex code (e.g., #0ea5e9).',
            'description.max' => 'Description cannot exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tag name',
            'color' => 'tag color',
        ];
    }
}
