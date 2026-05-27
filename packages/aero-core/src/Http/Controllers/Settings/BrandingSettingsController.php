<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreBrandingSettingsRequest;
use Aero\Core\Http\Resources\SystemSettingResource;
use Aero\Core\Models\SystemSetting;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandingSettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): Response|SystemSettingResource
    {
        $setting = SystemSetting::current();

        if ($request->wantsJson()) {
            return new SystemSettingResource($setting);
        }

        return Inertia::render('Core/Settings/Branding', [
            'title' => 'Branding & Appearance',
            'branding' => $setting->getBrandingPayload(),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'app_name' => ['sometimes', 'string', 'max:100'],
            'primary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sidebar_theme' => ['sometimes', 'in:dark,light'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:512'],
        ]);

        $setting = SystemSetting::current();

        // Handle file uploads to Spatie Media Library if files provided
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $setting->clearMediaCollection(SystemSetting::MEDIA_LOGO_LIGHT);
            $setting->addMediaFromRequest('logo')->toMediaCollection(SystemSetting::MEDIA_LOGO_LIGHT);
        }

        if ($request->hasFile('favicon') && $request->file('favicon')->isValid()) {
            $setting->clearMediaCollection(SystemSetting::MEDIA_FAVICON);
            $setting->addMediaFromRequest('favicon')->toMediaCollection(SystemSetting::MEDIA_FAVICON);
        }

        // Persist scalar branding values
        foreach (['app_name', 'primary_color', 'sidebar_theme'] as $key) {
            if ($request->has($key)) {
                $this->settings->set($key, $request->input($key));
            }
        }

        // Also merge into the branding JSON column for backward compat
        $existingBranding = $setting->branding ?? [];
        $brandingUpdate = array_filter([
            'primary_color' => $request->input('primary_color'),
            'sidebar_theme' => $request->input('sidebar_theme'),
        ], fn ($v) => $v !== null);

        if (! empty($brandingUpdate)) {
            $setting->update(['branding' => array_merge($existingBranding, $brandingUpdate)]);
        }

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            null,
            'Branding settings updated',
            null,
            null,
            ['section' => 'branding']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Branding settings updated successfully.',
                'branding' => $setting->refresh()->getBrandingPayload(),
            ]);
        }

        return back()->with('success', 'Branding settings saved.');
    }

    private function handleMediaUploads(SystemSetting $setting, StoreBrandingSettingsRequest $request): void
    {
        $mediaMap = [
            'logo_light' => SystemSetting::MEDIA_LOGO_LIGHT,
            'logo_dark' => SystemSetting::MEDIA_LOGO_DARK,
            'favicon' => SystemSetting::MEDIA_FAVICON,
            'login_background' => SystemSetting::MEDIA_LOGIN_BACKGROUND,
        ];

        foreach ($mediaMap as $inputKey => $collectionName) {
            if ($request->hasFile($inputKey) && $request->file($inputKey)->isValid()) {
                $setting
                    ->addMediaFromRequest($inputKey)
                    ->toMediaCollection($collectionName);
            }
        }
    }

    private function handleMediaRemovals(SystemSetting $setting, StoreBrandingSettingsRequest $request): void
    {
        $removalMap = [
            'remove_logo_light' => SystemSetting::MEDIA_LOGO_LIGHT,
            'remove_logo_dark' => SystemSetting::MEDIA_LOGO_DARK,
            'remove_favicon' => SystemSetting::MEDIA_FAVICON,
            'remove_login_background' => SystemSetting::MEDIA_LOGIN_BACKGROUND,
        ];

        foreach ($removalMap as $inputKey => $collectionName) {
            if ($request->boolean($inputKey)) {
                $setting->clearMediaCollection($collectionName);
            }
        }
    }
}
