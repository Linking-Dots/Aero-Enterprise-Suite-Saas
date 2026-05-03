<?php

namespace Aero\Core\Notifications;

use Aero\Core\Models\TenantInvitation;
use Aero\Notifications\Services\Mail\MailService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * InviteTeamMember Notification
 *
 * Sends an invitation email to a prospective team member with a secure,
 * token-based URL for accepting the invitation.
 *
 * Uses the unified MailService for reliable email delivery.
 */
class InviteTeamMember extends Notification
{
    /**
     * The tenant domain for URL generation.
     */
    protected string $tenantDomain;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public TenantInvitation $invitation
    ) {
        // Capture the tenant domain at dispatch time
        if (function_exists('tenant') && $tenant = tenant()) {
            $this->tenantDomain = $tenant->domains()->first()?->domain ?? request()->getHost();
        } else {
            $this->tenantDomain = request()->getHost();
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Send the invitation email using MailService.
     */
    public function sendEmail(): bool
    {
        $inviterName = $this->invitation->inviter?->name ?? 'The team administrator';
        $roleName = ucfirst($this->invitation->role);
        $expiresAt = $this->invitation->expires_at;
        $acceptUrl = $this->buildAcceptUrl();
        $organizationName = $this->getOrganizationName();
        $email = $this->invitation->email;

        $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #4F46E5;'>You've Been Invited! 🎉</h1>
                <p>Hello!</p>
                <p><strong>{$inviterName}</strong> has invited you to join <strong>{$organizationName}</strong> as a <strong>{$roleName}</strong>.</p>
                
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>Invitation Details:</h3>
                    <ul style='list-style: none; padding: 0;'>
                        <li>• <strong>Organization:</strong> {$organizationName}</li>
                        <li>• <strong>Role:</strong> {$roleName}</li>
                        <li>• <strong>Expires:</strong> {$expiresAt->format('F j, Y \\a\\t g:i A')}</li>
                    </ul>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$acceptUrl}' style='background: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Accept Invitation</a>
                </div>

                <p style='color: #666; font-size: 14px;'>If you did not expect this invitation, you can safely ignore this email.</p>

                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

                <p style='color: #666;'>Welcome to the team!<br>The {$organizationName} Team</p>
            </div>
        ";

        // Use platform settings if available (SaaS mode), otherwise use app config
        $mailService = app(MailService::class);

        if (method_exists($mailService, 'usePlatformSettings')) {
            $mailService->usePlatformSettings();
        }

        $result = $mailService->sendMail($email, "You've Been Invited to Join {$organizationName}", $html);

        if ($result['success']) {
            Log::info('Team invitation email sent', [
                'invitation_id' => $this->invitation->id,
                'email' => $email,
                'role' => $this->invitation->role,
            ]);
        } else {
            Log::error('Failed to send team invitation email', [
                'invitation_id' => $this->invitation->id,
                'email' => $email,
                'error' => $result['message'],
            ]);
        }

        return $result['success'];
    }

    /**
     * Get the organization name from tenant or config.
     */
    protected function getOrganizationName(): string
    {
        if (function_exists('tenant') && $tenant = tenant()) {
            return $tenant->name ?? config('app.name', 'Our Organization');
        }

        return config('app.name', 'Our Organization');
    }

    /**
     * Build the accept invitation URL with proper tenant domain.
     *
     * Security: The invitation token itself is cryptographically secure (UUID).
     * Additional validation is done in the controller to check expiration and status.
     */
    protected function buildAcceptUrl(): string
    {
        return "https://{$this->tenantDomain}/invitation/{$this->invitation->token}";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
            'role' => $this->invitation->role,
            'invited_by' => $this->invitation->invited_by,
            'expires_at' => $this->invitation->expires_at?->toISOString(),
        ];
    }
}
