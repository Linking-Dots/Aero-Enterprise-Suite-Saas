<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Infra;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\Infra\AddIpAllowlistRequest;
use Aero\Platform\Models\Infra\StaffIpAllowlist;
use Aero\Auth\Models\User;
use Aero\Platform\Services\Infra\PlatformSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSecurityController extends Controller
{
    public function __construct(private readonly PlatformSecurityService $svc) {}

    // -------------------------------------------------------------------------
    // Staff Sessions
    // -------------------------------------------------------------------------

    public function sessions(): Response
    {
        return Inertia::render('Platform/Admin/PlatformSecurity/Sessions', [
            'sessions' => $this->svc->listSessions(),
        ]);
    }

    public function forceLogout(Request $request, string $sessionId): RedirectResponse
    {
        $actorId = $request->user()->id ?? 0;
        $this->svc->forceLogout($sessionId, $actorId);

        return back()->with('success', 'Session terminated.');
    }

    // -------------------------------------------------------------------------
    // MFA
    // -------------------------------------------------------------------------

    public function mfaStatus(): Response
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'two_factor_secret', 'two_factor_confirmed_at']);

        return Inertia::render('Platform/Admin/PlatformSecurity/Security', [
            'tab' => 'mfa',
            'users' => $users->map(fn (User $u) => $this->svc->getMfaStatus($u)),
            'ipAllowlist' => $this->svc->getActiveIpAllowlist(),
        ]);
    }

    public function enforceMfa(User $user): RedirectResponse
    {
        $this->svc->enforceMfa($user);

        return back()->with('success', 'MFA enforcement set.');
    }

    public function resetMfa(User $user): RedirectResponse
    {
        $this->svc->resetMfa($user);

        return back()->with('success', 'MFA reset.');
    }

    // -------------------------------------------------------------------------
    // SSO
    // -------------------------------------------------------------------------

    public function sso(): Response
    {
        return Inertia::render('Platform/Admin/PlatformSecurity/Security', [
            'tab' => 'sso',
        ]);
    }

    public function configureSso(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'provider' => ['nullable', 'string', 'in:saml,oidc,google,azure'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:500'],
            'metadata_url' => ['nullable', 'url', 'max:500'],
        ]);

        $this->svc->configureSso($data);

        return back()->with('success', 'SSO configured.');
    }

    // -------------------------------------------------------------------------
    // IP Allowlist
    // -------------------------------------------------------------------------

    public function ipAllowlist(): Response
    {
        return Inertia::render('Platform/Admin/PlatformSecurity/Security', [
            'tab' => 'ip-allowlist',
            'ipAllowlist' => $this->svc->getActiveIpAllowlist(),
        ]);
    }

    public function addIp(AddIpAllowlistRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), [
            'created_by' => $request->user()->id ?? 0,
        ]);

        $this->svc->addIpAllowlist($data);

        return back()->with('success', 'IP entry added.');
    }

    public function removeIp(StaffIpAllowlist $entry): RedirectResponse
    {
        $this->svc->removeIpAllowlist($entry);

        return back()->with('success', 'IP entry removed.');
    }
}
