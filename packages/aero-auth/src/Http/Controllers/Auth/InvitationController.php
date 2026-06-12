<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Http\Requests\AcceptTeamInvitationRequest;
use Aero\Contracts\AuditServiceInterface;
use Aero\Contracts\UserInvitationServiceInterface;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Invitation Controller
 *
 * Handles public invitation acceptance flow.
 * Allows invited users to accept invitations and create their accounts.
 */
class InvitationController extends Controller
{
    public function __construct(
        protected UserInvitationServiceInterface $invitationService,
        protected AuditServiceInterface $auditService
    ) {}

    /**
     * Show the invitation acceptance form.
     *
     * @param  string  $token  The invitation token
     */
    public function showAcceptForm(string $token): Response
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (! $invitation) {
            return Inertia::render('Auth/InvitationInvalid', [
                'title' => 'Invalid Invitation',
                'message' => 'This invitation link is invalid or has been removed.',
            ]);
        }

        if ($invitation->isAccepted()) {
            return Inertia::render('Auth/InvitationInvalid', [
                'title' => 'Invitation Already Accepted',
                'message' => 'This invitation has already been used. Please login with your credentials.',
            ]);
        }

        if ($invitation->isExpired()) {
            return Inertia::render('Auth/InvitationInvalid', [
                'title' => 'Invitation Expired',
                'message' => 'This invitation has expired. Please contact your administrator to receive a new invitation.',
            ]);
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'title' => 'Accept Invitation',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'name' => $invitation->name,
                'roles' => $invitation->roles,
                'inviter' => $invitation->inviter ? [
                    'name' => $invitation->inviter->name,
                ] : null,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            'token' => $token,
        ]);
    }

    /**
     * Accept the invitation and create user account.
     *
     * @param  string  $token  The invitation token
     */
    public function accept(AcceptTeamInvitationRequest $request, string $token)
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (! $invitation) {
            return back()->withErrors([
                'token' => 'This invitation link is invalid.',
            ]);
        }

        if ($invitation->isAccepted()) {
            return back()->withErrors([
                'token' => 'This invitation has already been used.',
            ]);
        }

        if ($invitation->isExpired()) {
            return back()->withErrors([
                'token' => 'This invitation has expired. Please request a new invitation.',
            ]);
        }

        try {
            $user = $this->invitationService->acceptInvitation($token, [
                'name' => $request->input('name', $invitation->name),
                'password' => $request->input('password'),
            ]);

            // Log the acceptance
            $this->auditService->log(
                'invitation_accepted',
                'accepted',
                $invitation,
                "User {$user->name} ({$user->email}) accepted invitation and created account"
            );

            // Log the user in
            auth()->login($user);

            return redirect()->intended('/dashboard')->with('status', 'Welcome! Your account has been created successfully.');
        } catch (\Exception $e) {
            report($e);

            return back()->withErrors([
                'general' => 'Failed to create your account. Please try again or contact support.',
            ]);
        }
    }
}
