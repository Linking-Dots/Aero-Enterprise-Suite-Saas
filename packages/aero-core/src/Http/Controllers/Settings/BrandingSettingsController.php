<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Resources\SystemSettingResource;
use Aero\Core\Models\SystemSetting;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Branding\BrandingEntitlement;
use Aero\Core\Services\SystemSettingService;
use Aero\Kernel\Branding\BrandingPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tenant Brand Studio — the workspace's own white-label layer.
 * Every field is an override; anything unset inherits down the chain
 * (platform brand → Meridian). Editing is plan-gated in SaaS.
 */
class BrandingSettingsController extends Controller
{
    /** input key → media collection */
    private const MEDIA_MAP = [
        'logo_light' => SystemSetting::MEDIA_LOGO_LIGHT,
        'logo_dark' => SystemSetting::MEDIA_LOGO_DARK,
        'logo_icon' => SystemSetting::MEDIA_LOGO_ICON,
        'favicon' => SystemSetting::MEDIA_FAVICON,
        'login_background' => SystemSetting::MEDIA_LOGIN_BACKGROUND,
    ];

    private const SCALAR_KEYS = [
        'name', 'tagline', 'primary_color', 'accent_color',
        'sidebar_theme', 'email_from_name', 'email_from_address',
    ];

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

        return Inertia::render('Core/Settings/Index', [
            'section' => 'branding',
            'summary' => \Aero\Core\Services\SettingsSummary::build(),
            'branding' => $this->studioPayload($setting),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(
            BrandingEntitlement::allowed(),
            403,
            'White-label branding is not included in your plan.'
        );

        $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'app_name' => ['sometimes', 'nullable', 'string', 'max:100'], // legacy alias for name
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

        $setting = SystemSetting::current();

        DB::transaction(function () use ($request, $setting): void {
            // Removals first so an upload in the same save wins over a remove
            foreach (self::MEDIA_MAP as $key => $collection) {
                if ($request->boolean("remove_{$key}")) {
                    $setting->clearMediaCollection($collection);
                }
            }

            // Uploads (single-file collections replace atomically)
            foreach (self::MEDIA_MAP as $key => $collection) {
                if ($request->hasFile($key) && $request->file($key)->isValid()) {
                    $setting->clearMediaCollection($collection);
                    $setting->addMediaFromRequest($key)->toMediaCollection($collection);
                }
            }
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $setting->clearMediaCollection(SystemSetting::MEDIA_LOGO_LIGHT);
                $setting->addMediaFromRequest('logo')->toMediaCollection(SystemSetting::MEDIA_LOGO_LIGHT);
            }

            // Scalar overrides live in the branding JSON; empty string = clear override
            $branding = $setting->branding ?? [];
            foreach (self::SCALAR_KEYS as $key) {
                if (! $request->has($key) && ! ($key === 'name' && $request->has('app_name'))) {
                    continue;
                }
                $value = $key === 'name'
                    ? ($request->input('name') ?? $request->input('app_name'))
                    : $request->input($key);

                if ($value === null || $value === '') {
                    unset($branding[$key]);
                } else {
                    $branding[$key] = $value;
                }
            }
            // Keep the legacy app_name key in sync for older consumers
            if (isset($branding['name'])) {
                $branding['app_name'] = $branding['name'];
            } else {
                unset($branding['app_name']);
            }

            $setting->update(['branding' => $branding]);

            if (isset($branding['app_name'])) {
                $this->settings->set('app_name', $branding['app_name']);
            }
        });

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            null,
            'Brand Studio settings updated',
            null,
            null,
            ['section' => 'branding']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Branding updated.',
                'branding' => $this->studioPayload($setting->refresh()),
            ]);
        }

        return back()->with('success', 'Branding saved.');
    }

    /**
     * Drop every override — the workspace falls back down the chain
     * (platform brand → Meridian).
     */
    public function reset(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(
            BrandingEntitlement::allowed(),
            403,
            'White-label branding is not included in your plan.'
        );

        $setting = SystemSetting::current();

        DB::transaction(function () use ($setting): void {
            foreach (self::MEDIA_MAP as $collection) {
                $setting->clearMediaCollection($collection);
            }
            $setting->update(['branding' => []]);
        });

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            null,
            'Brand Studio reset to inherited defaults',
            null,
            null,
            ['section' => 'branding', 'action' => 'reset']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Branding reset.',
                'branding' => $this->studioPayload($setting->refresh()),
            ]);
        }

        return back()->with('success', 'Branding reset to defaults.');
    }

    /**
     * Full Brand Studio payload: raw overrides, the resolved chain,
     * the floor defaults, and the entitlement flag.
     */
    private function studioPayload(SystemSetting $setting): array
    {
        $overrides = $setting->getBrandingPayload();
        $overrides['name'] ??= $overrides['app_name'] ?? null;

        return [
            ...$overrides, // legacy shape (logo_light, favicon, …) for existing consumers
            'overrides' => $overrides,
            'resolved' => BrandingPayload::merge($overrides),
            'defaults' => BrandingPayload::defaults(),
            'entitled' => BrandingEntitlement::allowed(),
            'customized' => BrandingPayload::isCustomized($overrides),
        ];
    }
}
