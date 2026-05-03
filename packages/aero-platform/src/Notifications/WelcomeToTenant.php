<?php

namespace Aero\Platform\Notifications;

use Aero\Platform\Models\Tenant;
use Aero\Notifications\Services\Mail\MailService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * WelcomeToTenant Notification
 *
 * Sends a welcome email to the tenant admin after successful provisioning.
 * Uses the unified MailService for reliable email delivery.
 */
class WelcomeToTenant extends Notification
{
    public Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Send the welcome email using MailService.
     */
    public function sendEmail(string $email): bool
    {
        $domain = $this->tenant->domains()->where('is_primary', true)->first();
        $loginUrl = $domain ? "https://{$domain->domain}/login" : '#';
        $trialEndsAt = $this->tenant->subscription('default')?->trial_ends_at;
        $trialDays = $trialEndsAt
            ? now()->diffInDays($trialEndsAt)
            : config('platform.trial_days', 14);

        $appName = config('app.name');

        $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #4F46E5;'>🎉 Welcome to {$this->tenant->name}!</h1>
                <p>Your workspace has been successfully set up and is ready to use.</p>
                
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>Your Details:</h3>
                    <ul style='list-style: none; padding: 0;'>
                        <li>• <strong>Organization:</strong> {$this->tenant->name}</li>
                        <li>• <strong>Email:</strong> {$email}</li>
                        <li>• <strong>Workspace URL:</strong> <a href='{$loginUrl}'>{$loginUrl}</a></li>
                        <li>• <strong>Trial Period:</strong> {$trialDays} days</li>
                    </ul>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$loginUrl}' style='background: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Login to Your Workspace</a>
                </div>

                <h3>Getting Started:</h3>
                <ol>
                    <li>Complete your email verification</li>
                    <li>Set up your company profile</li>
                    <li>Invite your team members</li>
                    <li>Configure your modules and permissions</li>
                </ol>

                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

                <p><strong>Need Help?</strong></p>
                <p>Our support team is here to help you get started. Visit our documentation or contact <a href='mailto:support@eos365.com'>support@eos365.com</a></p>

                <p style='color: #666;'>Best regards,<br>The {$appName} Team</p>
            </div>
        ";

        $result = app(MailService::class)
            ->usePlatformSettings()
            ->sendMail($email, "🎉 Welcome to {$this->tenant->name}!", $html);

        if ($result['success']) {
            Log::info('Welcome email sent to tenant admin', [
                'tenant_id' => $this->tenant->id,
                'email' => $email,
            ]);
        } else {
            Log::error('Failed to send welcome email', [
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
            'type' => 'welcome',
            'message' => "Welcome to {$this->tenant->name}! Your workspace is ready.",
        ];
    }
}
