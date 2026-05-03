<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreLocalizationSettingsRequest;
use Aero\Core\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocalizationSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $setting = SystemSetting::current();

        return Inertia::render('Core/Settings/Localization', [
            'title' => 'Localization',
            'localization' => $setting->getLocalizationPayload(),
            'timezones' => $this->getTimezones(),
        ]);
    }

    public function update(StoreLocalizationSettingsRequest $request): JsonResponse
    {
        $setting = SystemSetting::current();

        $setting->update($request->validated());
        $setting->refresh();

        return response()->json([
            'message' => 'Localization settings updated successfully.',
            'localization' => $setting->getLocalizationPayload(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function getTimezones(): array
    {
        return \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
    }
}
