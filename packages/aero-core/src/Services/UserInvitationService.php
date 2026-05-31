<?php

namespace Aero\Core\Services;

use Aero\Core\Mail\UserInvitationMail;
use Aero\Core\Models\User;
use Aero\Core\Models\UserInvitation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserInvitationService
{
    /**
     * Send a user invitation
     */
    public function sendInvitation(array $data): UserInvitation
    {
        DB::beginTransaction();
        try {
            // Check if email already exists
            if (User::where('email', $data['email'])->exists()) {
                throw new \Exception('A user with this email already exists.');
            }

            // Check if pending invitation exists
            $existingInvitation = UserInvitation::where('email', $data['email'])
                ->pending()
                ->first();

            if ($existingInvitation) {
                // Resend existing invitation
                $invitation = $existingInvitation;
                $invitation->update([
                    'token' => Str::random(64),
                    'expires_at' => now()->addDays(7),
                    'invited_from_ip' => request()->ip(),
                ]);
            } else {
                // Create new invitation
                $invitation = UserInvitation::create([
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'token' => Str::random(64),
                    'roles' => $data['roles'] ?? [],
                    'metadata' => $data['metadata'] ?? [],
                    'invited_by' => auth()->id(),
                    'expires_at' => now()->addDays(7),
                    'invited_from_ip' => request()->ip(),
                ]);
            }

            // Send invitation email
            Mail::to($invitation->email)
                ->send(new UserInvitationMail($invitation));

            DB::commit();

            return $invitation;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Accept an invitation and create user account
     */
    public function acceptInvitation(string $token, array $userData): User
    {
        DB::beginTransaction();
        try {
            $invitation = UserInvitation::where('token', $token)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->firstOrFail();

            // Create user
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => \Hash::make($userData['password']),
                'active' => true,
                'email_verified_at' => now(),
            ]);

            // Assign roles
            if (! empty($invitation->roles)) {
                $user->syncRoles($invitation->roles);
            }

            // Mark invitation as accepted
            $invitation->markAsAccepted();

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Resend an invitation
     */
    public function resendInvitation(int $invitationId): UserInvitation
    {
        $invitation = UserInvitation::findOrFail($invitationId);

        if ($invitation->isAccepted()) {
            throw new \Exception('This invitation has already been accepted.');
        }

        // Generate new token and extend expiration
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        // Send invitation email
        Mail::to($invitation->email)
            ->send(new UserInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvitation(int $invitationId): bool
    {
        $invitation = UserInvitation::findOrFail($invitationId);

        if ($invitation->isAccepted()) {
            throw new \Exception('Cannot cancel an accepted invitation.');
        }

        return $invitation->delete();
    }

    /**
     * Get pending invitations
     */
    public function getPendingInvitations()
    {
        return UserInvitation::with('inviter')
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * List invitations with optional filters (used by CoreUserController).
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): Collection
    {
        return UserInvitation::with('inviter')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('email', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Invite a user by email with optional roles (used by CoreUserController).
     *
     * @param  array<int, string>  $roles
     */
    public function invite(string $email, array $roles, User $actor): UserInvitation
    {
        return $this->sendInvitation([
            'email' => $email,
            'name' => $email,
            'roles' => $roles,
        ]);
    }

    /**
     * Resend an invitation (used by CoreUserController).
     */
    public function resend(int $invitationId, User $actor): UserInvitation
    {
        return $this->resendInvitation($invitationId);
    }

    /**
     * Cancel an invitation (used by CoreUserController).
     */
    public function cancel(int $invitationId, User $actor): bool
    {
        return $this->cancelInvitation($invitationId);
    }

    /**
     * Clean up expired invitations
     */
    public function cleanupExpiredInvitations(): int
    {
        return UserInvitation::expired()->delete();
    }

    /**
     * Get invitation by token
     */
    public function getInvitationByToken(string $token): ?UserInvitation
    {
        return UserInvitation::where('token', $token)
            ->with('inviter')
            ->first();
    }
}
