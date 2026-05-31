<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Models\User;
use Aero\Core\Support\SafeRedirect;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantImpersonationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Tenant Impersonation Controller
 *
 * Handles incoming impersonation tokens from platform administrators.
 * Validates the token, logs the user in, and creates an audit log entry.
 *
 * Security features:
 * - Token validation (exists, not expired, correct tenant)
 * - Single-use tokens (consumed after use)
 * - Full audit logging for tenant transparency
 * - Session flagging for impersonation awareness
 */
class ImpersonationController extends Controller
{
    /**
     * Initiate impersonation from the admin panel.
     *
     * Called via POST /admin/tenants/{tenant}/impersonate by a landlord user.
     * Finds the tenant's first super admin user, generates a short-lived
     * single-use token, and returns the tenant-side redirect URL as JSON.
     * The frontend (Show.jsx) then navigates to that URL via window.location.
     */
    public function impersonate(Request $request, string $tenant): JsonResponse
    {
        $tenantModel = Tenant::findOrFail($tenant);

        if (! in_array($tenantModel->status, ['active', 'pending'])) {
            return response()->json([
                'message' => 'Cannot impersonate a tenant that is not active or pending.',
            ], 422);
        }

        // Initialize tenancy to access the tenant's database
        tenancy()->initialize($tenantModel);

        try {
            // Plan 05 T4 — find super admin via the canonical config list
            // (same source as CheckRoleModuleAccess middleware uses), NOT
            // via hardcoded 'Super Administrator' string match. Phase 1
            // audit flagged the single-name lookup as brittle: if the
            // tenant renamed their super-admin role or used a different
            // canonical name ('tenant_super_administrator', 'super-admin',
            // 'Tenant Super Administrator'), the lookup would silently
            // fall through to "oldest user" — risk of impersonating a
            // non-admin and leaking those privileges.
            $superAdminRoleNames = config('hrmac.super_admin_roles.web', [
                'Tenant Super Administrator',
                'tenant_super_administrator',
                'Super Administrator',
                'super-admin',
            ]);

            $user = User::whereHas('roles', fn ($q) => $q->whereIn('name', $superAdminRoleNames))
                ->orderBy('id')
                ->first()
                ?? User::orderBy('id')->first();
        } finally {
            tenancy()->end();
        }

        if (! $user) {
            return response()->json([
                'message' => 'No users found in this tenant workspace. Ask the tenant to set up their account first.',
            ], 422);
        }

        $token = TenantImpersonationToken::createForUser(
            $tenantModel->id,
            (string) $user->id,
            '/dashboard',
            'web'
        );

        $domain = $tenantModel->primaryDomain()?->domain
            ?? $tenantModel->domains()->first()?->domain;

        if (! $domain) {
            return response()->json(['message' => 'No domain configured for this tenant.'], 422);
        }

        $scheme = str_starts_with(config('app.url', ''), 'https') ? 'https' : 'http';
        $redirectUrl = $scheme.'://'.$domain.'/impersonate/'.$token->token;

        Log::info('Platform admin initiated tenant impersonation', [
            'tenant_id' => $tenantModel->id,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'admin_user_id' => auth('landlord')->id(),
            'ip' => $request->ip(),
        ]);

        return response()->json(['redirect_url' => $redirectUrl]);
    }

    /**
     * Handle an impersonation token and log the user in.
     */
    public function handle(Request $request, string $token): RedirectResponse
    {
        // Find and validate the token
        $impersonationToken = TenantImpersonationToken::findValid($token);

        if (! $impersonationToken) {
            Log::warning('Invalid or expired impersonation token used', [
                'token_prefix' => substr($token, 0, 16).'...',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect('/login')
                ->with('error', 'Invalid or expired impersonation link. Please request a new one from the platform administrator.');
        }

        // Validate tenant context
        $currentTenantId = tenant('id');
        if ($impersonationToken->tenant_id !== $currentTenantId) {
            Log::warning('Impersonation token tenant mismatch', [
                'token_tenant_id' => $impersonationToken->tenant_id,
                'current_tenant_id' => $currentTenantId,
                'ip' => $request->ip(),
            ]);

            // Consume the token to prevent retry attacks
            $impersonationToken->consume();

            return redirect('/login')
                ->with('error', 'This impersonation link is not valid for this tenant.');
        }

        // Find the target user
        $user = User::find($impersonationToken->user_id);

        if (! $user) {
            Log::warning('Impersonation target user not found', [
                'user_id' => $impersonationToken->user_id,
                'tenant_id' => $impersonationToken->tenant_id,
            ]);

            $impersonationToken->consume();

            return redirect('/login')
                ->with('error', 'The target user for impersonation no longer exists.');
        }

        // Check user is active
        if (! $user->active) {
            Log::warning('Impersonation target user is inactive', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            $impersonationToken->consume();

            return redirect('/login')
                ->with('error', 'Cannot impersonate an inactive user account.');
        }

        // Consume the token (single-use)
        $impersonationToken->consume();

        // Log the user in
        Auth::guard($impersonationToken->auth_guard)->login($user);

        // Flag the session as impersonated for UI awareness
        $request->session()->put('impersonated_by_platform', true);
        $request->session()->put('impersonation_started_at', now()->toIso8601String());

        // Regenerate session for security
        $request->session()->regenerate();

        // Create audit log entry for tenant transparency
        $this->logImpersonation($user, $request);

        Log::info('Platform admin impersonation successful', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tenant_id' => $currentTenantId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Plan 05 T3 — guard against open redirect via attacker-controlled
        // redirect_url. The token's redirect_url comes from the platform-admin
        // side, but if an attacker ever managed to forge or tamper with a
        // token, an external URL there would phish the impersonated user.
        // Falls back to /dashboard when the path isn't a safe relative URL.
        $requestedUrl = $impersonationToken->redirect_url ?? '/dashboard';
        $redirectUrl = SafeRedirect::isSafePath($requestedUrl) ? $requestedUrl : '/dashboard';

        if ($redirectUrl !== $requestedUrl) {
            Log::warning('Impersonation token redirect_url was unsafe; falling back to /dashboard', [
                'token_redirect_url' => $requestedUrl,
                'user_id'            => $user->id,
                'tenant_id'          => $currentTenantId,
                'ip'                 => $request->ip(),
            ]);
        }

        return redirect($redirectUrl)
            ->with('warning', 'You are being impersonated by a platform administrator. All actions are being logged.');
    }

    /**
     * Log the impersonation event for audit purposes.
     */
    protected function logImpersonation(User $user, Request $request): void
    {
        try {
            activity('security')
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties([
                    'event' => 'platform_admin_impersonation',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                    'notice' => 'Platform administrator accessed this account via impersonation.',
                ])
                ->log('Platform administrator impersonation started');
        } catch (\Exception $e) {
            // Don't fail the impersonation if logging fails
            Log::error('Failed to log impersonation event', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * End the impersonation session.
     * This logs out the impersonated session.
     */
    public function endImpersonation(Request $request): RedirectResponse
    {
        if (! $request->session()->has('impersonated_by_platform')) {
            return redirect('/dashboard');
        }

        /** @var User|null $user */
        $user = Auth::user();

        // Log the end of impersonation
        if ($user) {
            try {
                activity('security')
                    ->causedBy($user)
                    ->performedOn($user)
                    ->withProperties([
                        'event' => 'platform_admin_impersonation_ended',
                        'started_at' => $request->session()->get('impersonation_started_at'),
                        'ended_at' => now()->toIso8601String(),
                        'ip_address' => $request->ip(),
                    ])
                    ->log('Platform administrator impersonation ended');
            } catch (\Exception $e) {
                Log::error('Failed to log impersonation end event', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clear impersonation flags
        $request->session()->forget(['impersonated_by_platform', 'impersonation_started_at']);

        // Log out
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')
            ->with('success', 'Impersonation session ended.');
    }
}
