<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', 'in:users,roles,tags'],
            'file' => ['required', 'file', 'mimes:csv,json,txt', 'max:2048'],
            'format' => ['required', 'in:csv,json'],
            'options' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'entity_type.required' => 'Entity type is required',
            'entity_type.in' => 'Invalid entity type',
            'file.required' => 'File is required',
            'file.mimes' => 'File must be CSV or JSON format',
            'file.max' => 'File size must not exceed 2MB',
            'format.required' => 'Format is required',
            'format.in' => 'Format must be CSV or JSON',
        ];
    }
}
