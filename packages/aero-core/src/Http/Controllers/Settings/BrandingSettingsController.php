<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreBrandingSettingsRequest;
use Aero\Core\Http\Resources\SystemSettingResource;
use Aero\Core\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingSettingsController extends Controller
{
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

    public function update(StoreBrandingSettingsRequest $request): JsonResponse
    {
        $setting = SystemSetting::current();

        $validatedBranding = $request->validatedBranding();

        $existingBranding = $setting->branding ?? [];
        $mergedBranding = array_merge($existingBranding, $validatedBranding);

        $setting->update(['branding' => $mergedBranding]);

        $this->handleMediaUploads($setting, $request);
        $this->handleMediaRemovals($setting, $request);

        $setting->refresh();

        return response()->json([
            'message' => 'Branding settings updated successfully.',
            'branding' => $setting->getBrandingPayload(),
        ]);
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
