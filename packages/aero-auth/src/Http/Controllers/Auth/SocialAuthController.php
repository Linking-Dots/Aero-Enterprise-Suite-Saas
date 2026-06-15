<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Services\ModernAuthenticationService;
use Aero\Auth\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthController extends Controller
{
    /**
     * Supported OAuth providers.
     */
    protected array $supportedProviders = ['google', 'microsoft', 'github'];

    public function __construct(
        protected ModernAuthenticationService $authService
    ) {}

    /**
     * Redirect to OAuth provider for authentication.
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (! $this->isProviderSupported($provider)) {
            return redirect()->route('login')
                ->with('error', "OAuth provider '{$provider}' is not supported.");
        }

        if (! $this->isProviderConfigured($provider)) {
            return redirect()->route('login')
                ->with('error', "OAuth provider '{$provider}' is not configured.");
        }

        // Store intended URL for post-login redirect
        if ($request->has('intended')) {
            session(['url.intended' => $request->input('intended')]);
        }

        Log::info('OAuth redirect initiated', [
            'provider' => $provider,
            'ip' => $request->ip(),
        ]);

        return Socialite::driver($provider)
            ->scopes($this->getProviderScopes($provider))
            ->redirect();
    }

    /**
     * Handle callback from OAuth provider.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! $this->isProviderSupported($provider)) {
            return redirect()->route('login')
                ->with('error', "OAuth provider '{$provider}' is not supported.");
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

            Log::info('OAuth callback received', [
                'provider' => $provider,
                'email' => $socialUser->getEmail(),
                'id' => $socialUser->getId(),
            ]);

            // Find existing user by provider ID or email
            $user = $this->findOrCreateUser($socialUser, $provider);

            if (! $user) {
                return redirect()->route('login')
                    ->with('error', 'No account found with this email. Please contact your administrator.');
            }

            // Check if user is active (active = not soft-deleted)
            if ($user->trashed()) {
                $this->authService->logAuthenticationEvent(
                    $user,
                    'oauth_login_inactive_account',
                    'failure',
                    $request,
                    ['provider' => $provider]
                );

                return redirect()->route('login')
                    ->with('error', 'Your account has been deactivated.');
            }

            // Update OAuth tokens
            $this->updateOAuthTokens($user, $socialUser, $provider);

            // Log the user in
            Auth::login($user, true);

            $this->authService->logAuthenticationEvent(
                $user,
                'oauth_login_success',
                'success',
                $request,
                ['provider' => $provider]
            );

            Log::info('OAuth login successful', [
                'user_id' => $user->id,
                'provider' => $provider,
            ]);

            return redirect()->intended($this->getRedirectPath());

        } catch (InvalidStateException $e) {
            Log::warning('OAuth invalid state', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Authentication session expired. Please try again.');

        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'An error occurred during authentication. Please try again.');
        }
    }

    /**
     * Find existing user or create new one based on OAuth data.
     */
    protected function findOrCreateUser(object $socialUser, string $provider): ?User
    {
        $email = $socialUser->getEmail();
        $providerId = $socialUser->getId();

        // First, try to find by provider and provider ID
        $user = User::where('oauth_provider', $provider)
            ->where('oauth_provider_id', $providerId)
            ->first();

        if ($user) {
            return $user;
        }

        // Then, try to find by email
        $user = User::where('email', $email)->first();

        if ($user) {
            // Link the OAuth provider to existing account
            $user->update([
                'oauth_provider' => $provider,
                'oauth_provider_id' => $providerId,
            ]);

            Log::info('Linked OAuth provider to existing account', [
                'user_id' => $user->id,
                'provider' => $provider,
            ]);

            return $user;
        }

        // For this enterprise app, we don't auto-create users
        // Users must be created by administrators first
        return null;
    }

    /**
     * Update OAuth tokens for the user.
     */
    protected function updateOAuthTokens(User $user, object $socialUser, string $provider): void
    {
        $user->update([
            'oauth_provider' => $provider,
            'oauth_provider_id' => $socialUser->getId(),
            'oauth_token' => $socialUser->token,
            'oauth_refresh_token' => $socialUser->refreshToken ?? null,
            'oauth_token_expires_at' => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
            'avatar_url' => $socialUser->getAvatar(),
        ]);
    }

    /**
     * Check if provider is supported.
     */
    protected function isProviderSupported(string $provider): bool
    {
        return in_array($provider, $this->supportedProviders);
    }

    /**
     * Check if provider is configured with credentials.
     */
    protected function isProviderConfigured(string $provider): bool
    {
        $config = config("services.{$provider}");

        return ! empty($config['client_id']) && ! empty($config['client_secret']);
    }

    /**
     * Get provider-specific scopes.
     */
    protected function getProviderScopes(string $provider): array
    {
        return match ($provider) {
            'google' => ['openid', 'profile', 'email'],
            'microsoft' => ['openid', 'profile', 'email', 'User.Read'],
            'github' => ['user:email'],
            default => [],
        };
    }

    /**
     * Get the post-login redirect path.
     */
    protected function getRedirectPath(): string
    {
        // Check for tenant context
        if (tenant()) {
            return route('tenant.dashboard');
        }

        return route('admin.dashboard');
    }

    /**
     * List available OAuth providers for the frontend.
     */
    public function providers(Request $request): array
    {
        $providers = [];

        foreach ($this->supportedProviders as $provider) {
            if ($this->isProviderConfigured($provider)) {
                $providers[] = [
                    'name' => $provider,
                    'label' => $this->getProviderLabel($provider),
                    'icon' => $this->getProviderIcon($provider),
                    'url' => route('auth.social.redirect', ['provider' => $provider]),
                ];
            }
        }

        return $providers;
    }

    /**
     * Get human-readable provider label.
     */
    protected function getProviderLabel(string $provider): string
    {
        return match ($provider) {
            'google' => 'Google',
            'microsoft' => 'Microsoft',
            'github' => 'GitHub',
            default => Str::title($provider),
        };
    }

    /**
     * Get provider icon name (for frontend).
     */
    protected function getProviderIcon(string $provider): string
    {
        return match ($provider) {
            'google' => 'google',
            'microsoft' => 'microsoft',
            'github' => 'github',
            default => 'link',
        };
    }
}
