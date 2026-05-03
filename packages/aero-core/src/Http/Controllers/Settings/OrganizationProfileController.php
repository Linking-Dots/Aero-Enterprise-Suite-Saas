<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreOrganizationProfileRequest;
use Aero\Core\Http\Resources\SystemSettingResource;
use Aero\Core\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationProfileController extends Controller
{
    public function index(Request $request): Response|SystemSettingResource
    {
        $setting = SystemSetting::current();

        if ($request->wantsJson()) {
            return new SystemSettingResource($setting);
        }

        return Inertia::render('Core/Organization/Profile', [
            'title' => 'Organization Profile',
            'organization' => (new SystemSettingResource($setting))->resolve()['organization'] ?? [],
        ]);
    }

    public function update(StoreOrganizationProfileRequest $request): JsonResponse
    {
        $setting = SystemSetting::current();

        $validated = $request->validated();

        $setting->update($validated);

        return response()->json([
            'message' => 'Organization profile updated successfully.',
            'organization' => (new SystemSettingResource($setting))->resolve()['organization'] ?? [],
        ]);
    }
}
