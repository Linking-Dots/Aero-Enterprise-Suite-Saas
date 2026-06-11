<?php

namespace Aero\Notifications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
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
        $templateId = $this->route('id');

        return [
            'name' => 'required|string|max:255|unique:notification_templates,name,' . $templateId . ',id,tenant_id,' . tenant('id'),
            'subject' => 'required|string|max:255',
            'html_content' => 'required|string',
            'plain_content' => 'nullable|string',
            'category' => 'required|string|in:system,marketing,transactional,onboarding,billing',
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
