<?php

namespace Aero\Core\Http\Resources;

use Aero\Core\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SystemSetting $setting */
        $setting = $this->resource;

        return [
            'id' => $setting->id,
            'slug' => $setting->slug,
            'organization' => $setting->getOrganizationSummary(),
            'branding' => $setting->getBrandingPayload(),
            'localization' => $setting->getLocalizationPayload(),
            'metadata' => $setting->metadata ?? [],
            'email_settings' => $setting->getSanitizedEmailSettings(),
            'notification_channels' => $setting->notification_channels ?? [],
            'integrations' => $setting->integrations ?? [],
            'advanced' => $setting->advanced ?? [],
            'created_at' => $setting->created_at,
            'updated_at' => $setting->updated_at,
        ];
    }
}
