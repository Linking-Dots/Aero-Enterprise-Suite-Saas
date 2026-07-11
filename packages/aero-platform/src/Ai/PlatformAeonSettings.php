<?php

declare(strict_types=1);

namespace Aero\Platform\Ai;

use Aero\Contracts\Ai\AeonSettingsContract;
use Aero\Platform\Models\PlatformSetting;

/**
 * Binds Aeon's runtime settings to the platform control plane: the operator
 * configures provider / models / API key / global limits once in the platform
 * admin (platform_settings.ai_settings) and every tenant's Aeon reads them from
 * the central DB. Reads via the 'central' connection, so it resolves correctly
 * from tenant, central, and standalone contexts alike.
 */
class PlatformAeonSettings implements AeonSettingsContract
{
    /** @return array<string,mixed> */
    public function resolve(): array
    {
        return PlatformSetting::current()->getAiSettingsResolved();
    }
}
