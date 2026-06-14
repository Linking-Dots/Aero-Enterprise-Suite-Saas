<?php

namespace Aero\Auth\Mail;

use Aero\Auth\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * User Invitation Email
 *
 * Sends an invitation email to new users with a secure acceptance link.
 * Includes invitation details and expiration information.
 */
class UserInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public UserInvitation $invitation;

    public string $acceptUrl;

    public string $expiresAt;

    public ?string $invitedByName;

    /**
     * Create a new message instance.
     */
    public function __construct(UserInvitation $invitation)
    {
        $this->invitation = $invitation;
        $this->acceptUrl = $this->generateAcceptUrl($invitation);
        $this->expiresAt = $invitation->expires_at->format('M j, Y \a\t g:i A T');
        $this->invitedByName = $invitation->inviter?->name ?? 'System Administrator';

        // Set queue priority
        $this->onQueue('emails');
    }

    /**
     * Generate the invitation acceptance URL.
     */
    protected function generateAcceptUrl(UserInvitation $invitation): string
    {
        return url("/invitation/accept/{$invitation->token}");
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join '.config('app.name'),
            tags: ['invitation', 'onboarding'],
            metadata: [
                'invitation_id' => $this->invitation->id,
                'email' => $this->invitation->email,
                'type' => 'user_invitation',
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'aero-auth::emails.user-invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => $this->acceptUrl,
                'expiresAt' => $this->expiresAt,
                'invitedByName' => $this->invitedByName,
                'appName' => config('app.name'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
