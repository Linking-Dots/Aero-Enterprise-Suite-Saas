<?php

namespace Aero\Auth\Mail\Auth;

use Aero\Auth\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Password Changed Notification Email
 *
 * Sends a security notification when a user's password has been
 * successfully changed, including security context and next steps.
 */
class PasswordChangedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;

    public string $timestamp;

    public string $ipAddress;

    public ?string $location;

    public string $userAgent;

    public ?string $supportUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $ipAddress,
        ?string $location = null,
        ?string $userAgent = null,
        ?string $supportUrl = null
    ) {
        $this->user = $user;
        $this->timestamp = now()->format('M j, Y \a\t g:i A T');
        $this->ipAddress = $ipAddress;
        $this->location = $location;
        $this->userAgent = $userAgent ?? 'Unknown';
        $this->supportUrl = $supportUrl ?? route('support.security');

        // Set queue priority (security emails are high priority)
        $this->onQueue('security');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SECURITY] Password Changed - '.config('app.name'),
            tags: ['password-changed', 'security', 'notification'],
            metadata: [
                'user_id' => $this->user->id,
                'ip_address' => $this->ipAddress,
                'type' => 'password_changed',
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.password-changed-notification',
            with: [
                'user' => $this->user,
                'timestamp' => $this->timestamp,
                'ipAddress' => $this->ipAddress,
                'location' => $this->location,
                'userAgent' => $this->userAgent,
                'supportUrl' => $this->supportUrl,
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
