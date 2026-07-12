<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Services\PlatformSettingAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform Setting Controller (Admin)
 *
 * Handles all 6 settings sections:
 *   general, branding, email + test-send, localization, maintenance, infrastructure
 *
 * Route prefix: /admin/settings
 * Route names:  platform.admin.settings.*
 */
class PlatformSettingController extends Controller
{
    public function __construct(private PlatformSettingAdminService $svc) {}

    public function general(): Response
    {
        $s = $this->svc->current();

        return Inertia::render('Platform/Admin/Settings/General', [
            'settings' => [
                'site_name' => $s->site_name,
                'legal_name' => $s->legal_name,
                'tagline' => $s->tagline,
                'support_email' => $s->support_email,
                'support_phone' => $s->support_phone,
                'marketing_url' => $s->marketing_url,
                'timezone' => data_get($s->metadata, 'timezone', 'UTC'),
                'date_format' => data_get($s->metadata, 'date_format', 'Y-m-d'),
                'currency' => data_get($s->metadata, 'currency', 'USD'),
            ],
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:160'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'support_email' => ['nullable', 'email'],
            'support_phone' => ['nullable', 'string', 'max:64'],
            'marketing_url' => ['nullable', 'url'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'date_format' => ['nullable', 'string', 'max:32'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $this->svc->updateGeneral($data);

        return back()->with('success', 'General settings saved.');
    }

    /** input key → media collection (BrandStudio contract) */
    private const BRAND_MEDIA_MAP = [
        'logo_light' => PlatformSetting::MEDIA_LOGO_LIGHT,
        'logo_dark' => PlatformSetting::MEDIA_LOGO_DARK,
        'logo_icon' => PlatformSetting::MEDIA_SQUARE_LOGO,
        'favicon' => PlatformSetting::MEDIA_FAVICON,
        'login_background' => PlatformSetting::MEDIA_LOGIN_BACKGROUND,
    ];

    private const BRAND_SCALAR_KEYS = [
        'name', 'tagline', 'primary_color', 'accent_color',
        'sidebar_theme', 'email_from_name', 'email_from_address',
    ];

    public function branding(): Response
    {
        return Inertia::render('Platform/Admin/Settings/Branding', [
            'branding' => $this->brandingStudioPayload($this->svc->current()),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tagline' => ['sometimes', 'nullable', 'string', 'max:160'],
            'primary_color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sidebar_theme' => ['sometimes', 'nullable', 'in:dark,light'],
            'email_from_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email_from_address' => ['sometimes', 'nullable', 'email', 'max:190'],
            'logo_light' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'logo_icon' => ['nullable', 'image', 'max:1024'],
            'logo' => ['nullable', 'image', 'max:2048'], // legacy alias for logo_light
            'favicon' => ['nullable', 'image', 'max:512'],
            'login_background' => ['nullable', 'image', 'max:4096'],
            'remove_logo_light' => ['sometimes', 'boolean'],
            'remove_logo_dark' => ['sometimes', 'boolean'],
            'remove_logo_icon' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'remove_login_background' => ['sometimes', 'boolean'],
        ]);

        $setting = $this->svc->current();

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $setting): void {
            // Removals first so an upload in the same save wins over a remove
            foreach (self::BRAND_MEDIA_MAP as $key => $collection) {
                if ($request->boolean("remove_{$key}")) {
                    $setting->clearMediaCollection($collection);
                }
            }
            foreach (self::BRAND_MEDIA_MAP as $key => $collection) {
                if ($request->hasFile($key) && $request->file($key)->isValid()) {
                    $setting->clearMediaCollection($collection);
                    $setting->addMediaFromRequest($key)->toMediaCollection($collection);
                }
            }
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $setting->clearMediaCollection(PlatformSetting::MEDIA_LOGO_LIGHT);
                $setting->addMediaFromRequest('logo')->toMediaCollection(PlatformSetting::MEDIA_LOGO_LIGHT);
            }

            $branding = $setting->branding ?? [];
            foreach (self::BRAND_SCALAR_KEYS as $key) {
                if (! $request->has($key)) {
                    continue;
                }
                $value = $request->input($key);
                if ($value === null || $value === '') {
                    unset($branding[$key]);
                } else {
                    $branding[$key] = $value;
                }
            }

            $updates = ['branding' => $branding];
            // The platform brand name IS the site name — one fact, no drift.
            if ($request->filled('name')) {
                $updates['site_name'] = $request->input('name');
            }

            $setting->update($updates);
        });

        return back()->with('success', 'Branding saved.');
    }

    /** Drop every platform brand override — back to Meridian. */
    public function resetBranding(Request $request): RedirectResponse
    {
        $setting = $this->svc->current();

        \Illuminate\Support\Facades\DB::transaction(function () use ($setting): void {
            foreach (self::BRAND_MEDIA_MAP as $collection) {
                $setting->clearMediaCollection($collection);
            }
            $setting->clearMediaCollection(PlatformSetting::MEDIA_LOGO);
            $setting->update(['branding' => []]);
        });

        return back()->with('success', 'Platform branding reset to Meridian defaults.');
    }

    /** BrandStudio payload: overrides + resolved chain + Meridian floor. */
    private function brandingStudioPayload(PlatformSetting $setting): array
    {
        $layer = $setting->getBrandingPayload();
        $layer['name'] ??= $setting->site_name;
        $layer['tagline'] ??= $setting->tagline;

        return [
            ...$layer,
            'overrides' => $layer,
            'resolved' => \Aero\Kernel\Branding\BrandingPayload::merge($layer),
            'defaults' => \Aero\Kernel\Branding\BrandingPayload::defaults(),
            'entitled' => true,
            'customized' => \Aero\Kernel\Branding\BrandingPayload::isCustomized($layer),
        ];
    }

    public function email(): Response
    {
        return Inertia::render('Platform/Admin/Settings/Email', [
            'email' => $this->svc->current()->getSanitizedEmailSettings(),
        ]);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'host' => ['required', 'string'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'encryption' => ['nullable', 'in:tls,ssl'],
            'from_email' => ['required', 'email'],
            'from_name' => ['nullable', 'string', 'max:120'],
        ]);

        $this->svc->updateEmail($data);

        return back()->with('success', 'Email settings saved.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $data = $request->validate(['to' => ['required', 'email']]);
        $result = $this->svc->sendTestEmail($data['to']);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'Test email sent.' : ('Failed: '.$result['error'])
        );
    }

    public function localization(): Response
    {
        $s = $this->svc->current();

        return Inertia::render('Platform/Admin/Settings/Localization', [
            'localization' => [
                'default_locale' => data_get($s->metadata, 'default_locale', 'en'),
                'available_locales' => data_get($s->metadata, 'available_locales', ['en']),
                'timezone' => data_get($s->metadata, 'timezone', 'UTC'),
                'date_format' => data_get($s->metadata, 'date_format', 'Y-m-d'),
                'first_day_of_week' => data_get($s->metadata, 'first_day_of_week', 1),
            ],
        ]);
    }

    public function updateLocalization(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_locale' => ['required', 'string', 'max:8'],
            'available_locales' => ['required', 'array', 'min:1'],
            'timezone' => ['required', 'string', 'max:64'],
            'date_format' => ['required', 'string', 'max:32'],
            'first_day_of_week' => ['required', 'integer', 'between:0,6'],
        ]);

        $this->svc->updateLocalization($data);

        return back()->with('success', 'Localization saved.');
    }

    public function maintenance(): Response
    {
        return Inertia::render('Platform/Admin/Settings/Maintenance', [
            'maintenance' => PlatformSetting::getMaintenanceStatus(),
        ]);
    }

    public function toggleMaintenance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enable' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->svc->toggleMaintenance((bool) $data['enable'], $data['message'] ?? null);

        return back()->with('success', 'Maintenance status updated.');
    }

    public function infrastructure(): Response
    {
        return Inertia::render('Platform/Admin/Settings/Infrastructure', [
            'hosting' => $this->svc->current()->getSanitizedHostingSettings(),
        ]);
    }

    public function updateInfrastructure(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:shared,dedicated'],
            'cpanel_host' => ['nullable', 'string'],
            'cpanel_port' => ['nullable', 'integer', 'between:1,65535'],
            'cpanel_username' => ['nullable', 'string'],
            'cpanel_api_token' => ['nullable', 'string'],
            'cpanel_db_user' => ['nullable', 'string'],
        ]);

        $this->svc->updateInfrastructure($data);

        return back()->with('success', 'Infrastructure settings saved.');
    }
}
