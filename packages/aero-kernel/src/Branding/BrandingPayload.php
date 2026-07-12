<?php

declare(strict_types=1);

namespace Aero\Kernel\Branding;

/**
 * Canonical white-label branding payload shared by every tier.
 *
 * Resolution is a per-field fallback chain merged with merge():
 *   tenant override → platform default → Meridian (baked-in defaults()).
 *
 * Null/absent means "inherit from the next layer down". Asset keys hold URLs;
 * a null logo tells the frontend to render the built-in Meridian mark.
 */
final class BrandingPayload
{
    public const KEYS = [
        'name',              // brand / app name shown in shell, tab title, emails
        'tagline',
        'logo_light',        // URL — FULL lockup image (icon + text) for light surfaces
        'logo_dark',         // URL — FULL lockup image (icon + text) for dark surfaces
        'logo_icon',         // URL — square icon-only mark (collapsed rails, tight slots)
        'favicon',           // URL
        'login_background',  // URL or pattern token
        'primary_color',     // #rrggbb
        'accent_color',      // #rrggbb
        'sidebar_theme',     // dark | light
        'email_from_name',
        'email_from_address',
    ];

    /**
     * Meridian — the platform's own brand and the universal fallback.
     * Logo/favicon stay null: the frontend renders the bundled Meridian mark
     * and the host ships branding/web icons as static /favicon.ico.
     */
    public static function defaults(): array
    {
        return [
            'name' => 'aeos365',
            'tagline' => null,
            'logo_light' => null,
            'logo_dark' => null,
            'logo_icon' => null,
            'favicon' => null,
            'login_background' => null,
            'primary_color' => '#0C2742',
            'accent_color' => '#FF7A1F',
            'sidebar_theme' => 'dark',
            'email_from_name' => 'aeos365',
            'email_from_address' => null,
        ];
    }

    /**
     * Per-field merge, first layer wins. Pass layers most-specific first
     * (tenant, platform); defaults() is always appended as the floor.
     * Empty strings count as "not set". Unknown keys from the most-specific
     * layer are preserved (forward compat for section-specific extras).
     */
    public static function merge(array ...$layers): array
    {
        $layers[] = self::defaults();

        $out = [];
        foreach (self::KEYS as $key) {
            foreach ($layers as $layer) {
                $value = $layer[$key] ?? null;
                if ($value !== null && $value !== '') {
                    $out[$key] = $value;
                    break;
                }
            }
            $out[$key] ??= null;
        }

        // Preserve extras from the most-specific layer (e.g. media variant URLs)
        foreach ($layers[0] ?? [] as $key => $value) {
            if (! array_key_exists($key, $out)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /** True when any override layer set at least one visual field itself. */
    public static function isCustomized(array $layer): bool
    {
        foreach (['name', 'logo_light', 'logo_dark', 'logo_icon', 'favicon', 'login_background', 'primary_color', 'accent_color'] as $key) {
            if (($layer[$key] ?? null) !== null && ($layer[$key] ?? '') !== '') {
                return true;
            }
        }

        return false;
    }
}
