<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MobileController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function getConfig(): object
    {
        return DB::table('pwa_configs')->first() ?? (object) [
            'pwa_enabled' => false, 'display_name' => null, 'short_name' => null,
            'theme_color' => '#006FEE', 'background_color' => '#ffffff',
            'display_mode' => 'standalone', 'icon_path' => null,
            'push_enabled' => false, 'vapid_public_key' => null,
            'mobile_app_enabled' => false, 'android_package' => null,
            'ios_bundle_id' => null, 'deep_link_schemes' => null,
        ];
    }

    public function index(): Response
    {
        $config = $this->getConfig();
        $manifest = [
            'name' => $config->display_name ?? config('app.name'),
            'short_name' => $config->short_name ?? config('app.name'),
            'theme_color' => $config->theme_color ?? '#006FEE',
            'background_color' => $config->background_color ?? '#ffffff',
            'display' => $config->display_mode ?? 'standalone',
        ];

        return Inertia::render('Core/Mobile/Index', ['config' => $config, 'manifest' => $manifest]);
    }

    public function updatePwa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pwa_enabled' => ['boolean'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'short_name' => ['nullable', 'string', 'max:30'],
            'theme_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'display_mode' => ['nullable', 'in:standalone,fullscreen,minimal-ui,browser'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('pwa/icons', 'public');
            $data['icon_path'] = Storage::url($path);
        }
        unset($data['icon']);

        DB::table('pwa_configs')->updateOrInsert(
            ['id' => 1],
            array_merge($data, ['updated_at' => now(), 'created_at' => now()])
        );
        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'updated', null, 'PWA config updated');

        return back()->with('success', 'PWA settings saved.');
    }

    public function updatePush(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'push_enabled' => ['boolean'],
            'vapid_public_key' => ['nullable', 'string'],
            'vapid_private_key' => ['nullable', 'string'],
        ]);
        DB::table('pwa_configs')->updateOrInsert(
            ['id' => 1],
            array_merge($data, ['updated_at' => now(), 'created_at' => now()])
        );
        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'updated', null, 'Push config updated');

        return back()->with('success', 'Push notification settings saved.');
    }

    public function updateMobileApp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mobile_app_enabled' => ['boolean'],
            'android_package' => ['nullable', 'string'],
            'ios_bundle_id' => ['nullable', 'string'],
            'deep_link_schemes' => ['nullable', 'array'],
        ]);

        if (isset($data['deep_link_schemes'])) {
            $data['deep_link_schemes'] = json_encode($data['deep_link_schemes']);
        }

        DB::table('pwa_configs')->updateOrInsert(
            ['id' => 1],
            array_merge($data, ['updated_at' => now(), 'created_at' => now()])
        );
        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'updated', null, 'Mobile app config updated');

        return back()->with('success', 'Mobile app settings saved.');
    }
}
