<?php

namespace Aero\Notifications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:notification_templates,name,NULL,id,tenant_id,' . tenant('id'),
            'subject' => 'required|string|max:255',
            'html_content' => 'required|string',
            'plain_content' => 'nullable|string',
            'category' => 'required|string|in:system,marketing,transactional,onboarding,billing',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'template name',
            'subject' => 'subject line',
            'html_content' => 'HTML content',
            'plain_content' => 'plain text content',
            'category' => 'category',
        ];
    }
}
