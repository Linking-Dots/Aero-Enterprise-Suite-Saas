<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Models\User;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * AdminSetupController
 *
 * Handles the initial admin user creation for a newly provisioned tenant.
 * This controller is accessed AFTER the tenant database has been provisioned
 * and before the tenant is fully operational.
 *
 * Flow:
 * 1. Platform registration creates tenant record (pending status)
 * 2. ProvisionTenant job creates database, runs migrations, seeds roles/permissions
 * 3. Tenant is activated (status = active, needs_admin_setup = true)
 * 4. User is redirected to tenant domain's /admin-setup page
 * 5. This controller handles admin user creation
 * 6. Once admin is created, needs_admin_setup is set to false
 */
class AdminSetupController extends Controller
{
    /**
     * Show the admin setup form.
     *
     * This page is shown when a tenant has been provisioned but doesn't yet
     * have an admin user. Access is only allowed for tenants in the
     * 'needs_admin_setup' state.
     */
    public function show(): Response|RedirectResponse
    {
        $tenant = tenant();

        // Only allow admin setup for active tenants that don't have admin yet
        if (! $tenant || $tenant->status !== 'active') {
            abort(404);
        }

        // If tenant already has admin user, redirect to login
        if ($this->tenantHasAdminUser()) {
            return $this->redirectToLogin('Admin account already exists. Please login.', 'info');
        }

        return Inertia::render('Shared/Auth/AdminSetup', [
            'title' => 'Complete Your Account Setup',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
        ]);
    }

    /**
     * Store the admin user details.
     *
     * Creates the initial admin user for the tenant and assigns the
     * Super Administrator role.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = tenant();

        // Only allow admin setup for active tenants
        if (! $tenant || $tenant->status !== 'active') {
            abort(404);
        }

        // Prevent creating duplicate admin users
        if ($this->tenantHasAdminUser()) {
            return $this->redirectToLogin('Admin account already exists.', 'error');
        }

        // Validate admin user data
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_name' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,user_name'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'name.required' => 'Please enter your full name.',
            'user_name.required' => 'Please choose a username.',
            'user_name.alpha_dash' => 'Username can only contain letters, numbers, dashes and underscores.',
            'user_name.unique' => 'This username is already taken.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Please create a password.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        try {
            // Create the admin user
            // Admin user email/phone are independent and don't require verification
            $user = User::create([
                'name' => $validated['name'],
                'user_name' => $validated['user_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(), // Mark as verified - no verification needed
                'phone_verified_at' => ! empty($validated['phone']) ? now() : null,
            ]);

            Log::info('Admin user created for tenant', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            // Assign Super Administrator role BEFORE login
            $this->assignSuperAdminRole($user);

            // Refresh user to ensure role is loaded
            $user->refresh();
            $user->load('roles');

            // Mark tenant as having admin setup complete
            $this->markAdminSetupComplete($tenant);

            // Log the user in and regenerate session
            auth()->login($user, true); // Remember me = true
            request()->session()->regenerate(); // Regenerate session for security

            Log::info('Admin user logged in and tenant setup complete', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'email_verified' => ! is_null($user->email_verified_at),
                'roles' => $user->roles->pluck('name')->toArray(),
                'has_super_admin_role' => $user->hasRole('Super Administrator'),
            ]);

            // Redirect to onboarding if platform package is installed (SaaS mode)
            // Otherwise redirect to dashboard (standalone mode)
            $redirectRoute = class_exists('Aero\Platform\Http\Controllers\TenantOnboardingController')
                ? 'onboarding.index'
                : 'dashboard';

            return redirect()->route($redirectRoute)
                ->with('success', 'Welcome! Your admin account has been created.');

        } catch (\Throwable $e) {
            Log::error('Failed to create admin user', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'email' => 'Failed to create admin account. Please try again.',
            ]);
        }
    }

    /**
     * Check if tenant already has an admin user.
     */
    protected function tenantHasAdminUser(): bool
    {
        return User::query()->exists();
    }

    /**
     * Assign Super Administrator role to user.
     */
    protected function assignSuperAdminRole(User $user): void
    {
        try {
            $role = Role::where('name', 'Super Administrator')->first();

            if (! $role) {
                Log::warning('Super Administrator role not found, attempting to create', [
                    'user_id' => $user->id,
                ]);

                // Create the role if it doesn't exist
                $role = Role::create(['name' => 'Super Administrator', 'guard_name' => 'web']);
            }

            $user->assignRole($role);

            Log::info('Super Administrator role assigned to user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to assign Super Administrator role', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - user is created, role assignment is secondary
        }
    }

    /**
     * Mark tenant as having completed admin setup.
     */
    protected function markAdminSetupComplete(Tenant $tenant): void
    {
        try {
            // Update tenant data to indicate admin setup is complete
            $data = $tenant->data ?? [];
            $data['admin_setup_completed'] = true;
            $data['admin_setup_completed_at'] = now()->toISOString();

            // Use central connection to update tenant record
            tenancy()->central(function () use ($tenant, $data) {
                $tenant->update([
                    'data' => $data,
                ]);
            });

            Log::info('Tenant admin setup marked as complete', [
                'tenant_id' => $tenant->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to mark admin setup as complete', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - this is just metadata
        }
    }

    /**
     * Redirect to login with a safe fallback when the named route is unavailable.
     */
    protected function redirectToLogin(string $message, string $flashKey = 'info'): RedirectResponse
    {
        try {
            return redirect()->route('login')->with($flashKey, $message);
        } catch (RouteNotFoundException) {
            return redirect('/login')->with($flashKey, $message);
        }
    }
}
