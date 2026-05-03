<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo_light' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,gif,svg,webp'],
            'logo_dark' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,gif,svg,webp'],
            'favicon' => ['nullable', 'image', 'max:1024', 'mimes:ico,png,webp'],
            'login_background' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,png,webp'],
            'remove_logo_light' => ['nullable', 'boolean'],
            'remove_logo_dark' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'remove_login_background' => ['nullable', 'boolean'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'branding' => ['nullable', 'array'],
            'branding.font_family' => ['nullable', 'string', 'max:100'],
            'branding.button_radius' => ['nullable', 'string', 'in:none,sm,md,lg,full'],
            'branding.show_company_name_header' => ['nullable', 'boolean'],
            'branding.show_logo_on_login' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'logo_light' => 'Light Logo',
            'logo_dark' => 'Dark Logo',
            'favicon' => 'Favicon',
            'login_background' => 'Login Background',
            'primary_color' => 'Primary Color',
            'accent_color' => 'Accent Color',
            'branding.font_family' => 'Font Family',
            'branding.button_radius' => 'Button Radius',
            'branding.show_company_name_header' => 'Show Company Name in Header',
            'branding.show_logo_on_login' => 'Show Logo on Login',
        ];
    }

    public function validatedBranding(): array
    {
        $branding = $this->validated('branding', []);

        $colors = array_filter([
            'primary_color' => $this->validated('primary_color'),
            'accent_color' => $this->validated('accent_color'),
        ]);

        return array_merge($branding, $colors);
    }
}
