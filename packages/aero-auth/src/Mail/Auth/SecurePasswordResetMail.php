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
 * Secure Password Reset Email
 *
 * Sends a secure OTP-based password reset email with comprehensive
 * security information and context for audit trails.
 */
class SecurePasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;

    public string $otp;

    public string $resetUrl;

    public string $timestamp;

    public string $ipAddress;

    public ?string $location;

    public string $userAgent;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $otp,
        string $resetUrl,
        string $ipAddress,
        ?string $location = null,
        ?string $userAgent = null
    ) {
        $this->user = $user;
        $this->otp = $otp;
        $this->resetUrl = $resetUrl;
        $this->timestamp = now()->format('M j, Y \a\t g:i A T');
        $this->ipAddress = $ipAddress;
        $this->location = $location;
        $this->userAgent = $userAgent ?? 'Unknown';

        // Set queue priority (security emails are high priority)
        $this->onQueue('security');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SECURITY] Password Reset Request - '.config('app.name'),
            tags: ['password-reset', 'security'],
            metadata: [
                'user_id' => $this->user->id,
                'ip_address' => $this->ipAddress,
                'type' => 'password_reset',
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.secure-password-reset',
            with: [
                'user' => $this->user,
                'otp' => $this->otp,
                'resetUrl' => $this->resetUrl,
                'timestamp' => $this->timestamp,
                'ipAddress' => $this->ipAddress,
                'location' => $this->location,
                'userAgent' => $this->userAgent,
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
