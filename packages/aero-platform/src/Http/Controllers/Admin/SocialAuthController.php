<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Auth\Models\SocialAuthAccount;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Services\Marketing\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Social Auth Controller
 *
 * Manages social authentication providers from the platform admin.
 */
class SocialAuthController extends Controller
{
    public function __construct(
        protected SocialAuthService $socialAuthService
    ) {}

    /**
     * Display social auth settings.
     */
    public function index(): Response
    {
        $settings = PlatformSetting::current()->getSocialAuthSettings();
        $supportedProviders = SocialAuthAccount::getSupportedProviders();

        $providers = collect($supportedProviders)->map(function ($config, $provider) use ($settings) {
            $providerSettings = $settings['providers'][$provider] ?? [];

            return [
                'provider' => $provider,
                'name' => $config['name'],
                'icon' => $config['icon'],
                'color' => $config['color'],
                'enabled' => $providerSettings['enabled'] ?? false,
                'configured' => ! empty($providerSettings['client_id']) && ! empty($providerSettings['client_secret']),
            ];
        })->values();

        $stats = $this->getSocialAuthStats();

        return Inertia::render('Platform/Admin/SocialAuth/Index', [
            'title' => 'Social Authentication',
            'providers' => $providers,
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    /**
     * Show provider configuration form.
     */
    public function showProvider(string $provider): Response
    {
        $supportedProviders = SocialAuthAccount::getSupportedProviders();

        if (! isset($supportedProviders[$provider])) {
            abort(404, 'Provider not supported.');
        }

        $settings = PlatformSetting::current()->getSocialAuthSettings();
        $providerSettings = $settings['providers'][$provider] ?? [];
        $providerConfig = $supportedProviders[$provider];

        return Inertia::render('Platform/Admin/SocialAuth/Provider', [
            'title' => $providerConfig['name'].' Configuration',
            'provider' => $provider,
            'providerConfig' => $providerConfig,
            'settings' => $providerSettings,
            'callbackUrl' => url("/auth/{$provider}/callback"),
        ]);
    }

    /**
     * Update provider settings.
     */
    public function updateProvider(Request $request, string $provider): JsonResponse
    {
        $supportedProviders = SocialAuthAccount::getSupportedProviders();

        if (! isset($supportedProviders[$provider])) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not supported.',
            ], 404);
        }

        $validated = $request->validate([
            'enabled' => 'boolean',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'scopes' => 'nullable|array',
            'options' => 'nullable|array',
        ]);

        $platformSettings = PlatformSetting::current();
        $socialSettings = $platformSettings->getSocialAuthSettings();

        $socialSettings['providers'][$provider] = array_merge(
            $socialSettings['providers'][$provider] ?? [],
            $validated
        );

        $platformSettings->update(['social_auth_settings' => $socialSettings]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($provider).' settings updated successfully.',
            'data' => $socialSettings['providers'][$provider],
        ]);
    }

    /**
     * Toggle provider status.
     */
    public function toggleProvider(string $provider): JsonResponse
    {
        $supportedProviders = SocialAuthAccount::getSupportedProviders();

        if (! isset($supportedProviders[$provider])) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not supported.',
            ], 404);
        }

        $platformSettings = PlatformSetting::current();
        $socialSettings = $platformSettings->getSocialAuthSettings();

        $currentEnabled = $socialSettings['providers'][$provider]['enabled'] ?? false;

        // Check if provider is configured before enabling
        if (
            ! $currentEnabled &&
            (empty($socialSettings['providers'][$provider]['client_id']) ||
             empty($socialSettings['providers'][$provider]['client_secret']))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure client ID and secret before enabling.',
            ], 400);
        }

        $socialSettings['providers'][$provider]['enabled'] = ! $currentEnabled;
        $platformSettings->update(['social_auth_settings' => $socialSettings]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($provider).' '.($currentEnabled ? 'disabled' : 'enabled').' successfully.',
            'enabled' => ! $currentEnabled,
        ]);
    }

    /**
     * List all linked social accounts.
     */
    public function accounts(Request $request): Response
    {
        $filters = $request->only(['search', 'provider']);
        $perPage = $request->input('perPage', 20);

        $query = SocialAuthAccount::with('authenticatable')
            ->whereNotNull('authenticatable_id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        $accounts = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('Platform/Admin/SocialAuth/Accounts', [
            'title' => 'Linked Social Accounts',
            'accounts' => $accounts,
            'filters' => $filters,
            'providers' => array_keys(SocialAuthAccount::getSupportedProviders()),
        ]);
    }

    /**
     * Delete a linked social account.
     */
    public function destroyAccount(SocialAuthAccount $account): JsonResponse
    {
        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Social account unlinked successfully.',
        ]);
    }

    /**
     * Get social auth statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->getSocialAuthStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Calculate social auth statistics.
     */
    protected function getSocialAuthStats(): array
    {
        $byProvider = SocialAuthAccount::whereNotNull('authenticatable_id')
            ->selectRaw('provider, count(*) as count')
            ->groupBy('provider')
            ->pluck('count', 'provider')
            ->toArray();

        $total = array_sum($byProvider);
        $recent = SocialAuthAccount::whereNotNull('authenticatable_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $pending = SocialAuthAccount::whereNull('authenticatable_id')
            ->whereNotNull('pending_registration_token')
            ->where('pending_expires_at', '>', now())
            ->count();

        return [
            'total' => $total,
            'by_provider' => $byProvider,
            'recent_30d' => $recent,
            'pending_registrations' => $pending,
        ];
    }

    /**
     * Update general social auth settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'allow_registration' => 'boolean',
            'allow_login' => 'boolean',
            'link_existing_accounts' => 'boolean',
            'require_email_verification' => 'boolean',
        ]);

        $platformSettings = PlatformSetting::current();
        $socialSettings = $platformSettings->getSocialAuthSettings();

        $socialSettings = array_merge($socialSettings, $validated);
        $platformSettings->update(['social_auth_settings' => $socialSettings]);

        return response()->json([
            'success' => true,
            'message' => 'Social auth settings updated successfully.',
            'data' => $socialSettings,
        ]);
    }
}
