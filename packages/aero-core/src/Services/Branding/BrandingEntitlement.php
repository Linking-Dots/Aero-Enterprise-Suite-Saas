<?php

declare(strict_types=1);

namespace Aero\Core\Services\Branding;

/**
 * Gate for tenant white-label editing. Inheriting the platform brand is
 * always free; changing your own requires the `white_label` plan feature
 * (legacy key `custom_branding` honoured). Standalone installs own their
 * brand outright.
 */
final class BrandingEntitlement
{
    public static function allowed(): bool
    {
        if (function_exists('aero_mode') && aero_mode() !== 'saas') {
            return true;
        }

        try {
            $plan = function_exists('tenant') ? tenant()?->plan : null;
            if (! $plan) {
                return false;
            }

            return (bool) ($plan->features['white_label']
                ?? $plan->features['custom_branding']
                ?? false);
        } catch (\Throwable) {
            return false;
        }
    }
}
