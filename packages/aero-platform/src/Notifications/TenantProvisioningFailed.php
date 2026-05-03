<?php

namespace Aero\Platform\Notifications;

use Aero\Platform\Models\Tenant;
use Aero\Notifications\Services\Mail\MailService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * TenantProvisioningFailed Notification
 *
 * Notifies the tenant admin when their workspace provisioning has failed.
 * Uses the unified MailService for reliable email delivery.
 */
class TenantProvisioningFailed extends Notification
{
    public Tenant $tenant;

    public string $errorMessage;

    public function __construct(Tenant $tenant, string $errorMessage = '')
    {
        $this->tenant = $tenant;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Send the failure notification email using MailService.
     */
    public function sendEmail(string $email): bool
    {
        $supportUrl = config('app.url').'/support';
        $retryUrl = config('app.url').'/register';
        $appName = config('app.name');

        $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #DC2626;'>⚠️ Workspace Setup Issue</h1>
                <p>Hello,</p>
                <p>We encountered an issue while setting up your workspace: <strong>{$this->tenant->name}</strong></p>
                
                <p>Our team has been automatically notified and is investigating the issue.</p>

                <div style='background: #FEF2F2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #DC2626;'>
                    <h3 style='margin-top: 0; color: #DC2626;'>What happens next?</h3>
                    <ul>
                        <li>Our technical team will review the error</li>
                        <li>We'll attempt to resolve the issue automatically</li>
                        <li>You'll receive an update within 1 business hour</li>
                    </ul>
                </div>

                <h3>Need immediate assistance?</h3>
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='{$supportUrl}' style='background: #DC2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-right: 10px;'>Contact Support</a>
                    <a href='{$retryUrl}' style='background: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Try Again</a>
                </div>

                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

                <p>We apologize for the inconvenience and appreciate your patience.</p>
                <p style='color: #666;'>Best regards,<br>The {$appName} Support Team</p>
            </div>
        ";

        $result = app(MailService::class)
            ->usePlatformSettings()
            ->sendMail($email, "⚠️ Workspace Setup Issue - {$this->tenant->name}", $html);

        if ($result['success']) {
            Log::info('Provisioning failure email sent', [
                'tenant_id' => $this->tenant->id,
                'email' => $email,
            ]);
        } else {
            Log::error('Failed to send provisioning failure email', [
                'tenant_id' => $this->tenant->id,
                'email' => $email,
                'error' => $result['message'],
            ]);
        }

        return $result['success'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'error' => $this->errorMessage,
            'type' => 'provisioning_failed',
            'message' => "Workspace setup failed for {$this->tenant->name}",
        ];
    }
}
