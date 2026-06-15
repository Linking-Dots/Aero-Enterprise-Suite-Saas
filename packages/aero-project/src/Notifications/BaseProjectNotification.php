<?php

declare(strict_types=1);

namespace Aero\Project\Notifications;

use Aero\HRMAC\Facades\HRMAC;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Base Project Notification
 *
 * All Project notifications extend this class to get:
 * - HRMAC-aware recipient resolution
 * - Consistent channel resolution
 * - Common FCM and SMS channel support
 * - Consistent queue configuration
 *
 * ARCHITECTURAL RULE: This class does NOT import Core User or HRM Employee models.
 * HRMAC facade handles user resolution internally.
 */
abstract class BaseProjectNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The notification event type for preference lookup.
     */
    protected string $eventType = '';

    /**
     * Module code for HRMAC routing.
     */
    protected string $moduleCode = 'project';

    /**
     * Sub-module code for HRMAC routing.
     */
    protected string $subModuleCode = '';

    /**
     * Component code for HRMAC routing (optional).
     */
    protected ?string $componentCode = null;

    /**
     * Action code for HRMAC routing.
     */
    protected string $actionCode = 'view';

    /**
     * Retry settings.
     */
    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Default channels - can be overridden per notification
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getMailSubject())
            ->line($this->getMailLine())
            ->action($this->getMailActionText(), $this->getMailActionUrl())
            ->line('Thank you for using our project management system!');
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->eventType,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'action_url' => $this->getMailActionUrl(),
            'data' => $this->getNotificationData(),
        ];
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array{title: string, body: string, data: array}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->getFcmTitle(),
            'body' => $this->getFcmBody(),
            'data' => $this->getFcmData(),
        ];
    }

    /**
     * Get users who should receive this notification based on HRMAC access.
     * Uses HRMAC facade to resolve recipients without direct model imports.
     *
     * @return Collection Collection of User models
     */
    public function getHrmacRecipients(): Collection
    {
        if ($this->componentCode !== null) {
            // Component-level access check
            return HRMAC::getUsersWithActionAccess(
                $this->moduleCode,
                $this->subModuleCode,
                $this->componentCode,
                $this->actionCode
            );
        }

        // Sub-module level access check
        return HRMAC::getUsersWithSubModuleAccess(
            $this->moduleCode,
            $this->subModuleCode,
            $this->actionCode
        );
    }

    /**
     * Get additional direct recipients (e.g., task assignee, project leader).
     * Override in child classes to add direct recipients.
     *
     * @return array<int> Array of user IDs
     */
    public function getDirectRecipientIds(): array
    {
        return [];
    }

    /**
     * Combine HRMAC recipients with direct recipients.
     *
     * @return Collection Collection of User models
     */
    public function getAllRecipients(): Collection
    {
        $hrmacRecipients = $this->getHrmacRecipients();
        $directIds = $this->getDirectRecipientIds();

        if (empty($directIds)) {
            return $hrmacRecipients;
        }

        // Get User model class from config to avoid direct import
        $userModel = config('hrmac.models.user', \Aero\Core\Models\User::class);

        // Get direct recipients
        $directRecipients = $userModel::whereIn('id', $directIds)
            ->whereNull('deleted_at') // active = not soft-deleted
            ->get();

        // Merge and deduplicate
        return $hrmacRecipients->merge($directRecipients)->unique('id');
    }

    /**
     * Get mail subject line.
     */
    abstract protected function getMailSubject(): string;

    /**
     * Get mail body line.
     */
    abstract protected function getMailLine(): string;

    /**
     * Get mail action button text.
     */
    protected function getMailActionText(): string
    {
        return 'View Details';
    }

    /**
     * Get mail action URL.
     */
    abstract protected function getMailActionUrl(): string;

    /**
     * Get notification title for database/FCM.
     */
    protected function getNotificationTitle(): string
    {
        return $this->getMailSubject();
    }

    /**
     * Get notification message for database/FCM.
     */
    protected function getNotificationMessage(): string
    {
        return $this->getMailLine();
    }

    /**
     * Get additional notification data for database storage.
     */
    protected function getNotificationData(): array
    {
        return [];
    }

    /**
     * Get FCM notification title.
     */
    protected function getFcmTitle(): string
    {
        return $this->getNotificationTitle();
    }

    /**
     * Get FCM notification body.
     */
    protected function getFcmBody(): string
    {
        return $this->getNotificationMessage();
    }

    /**
     * Get FCM notification data payload.
     */
    protected function getFcmData(): array
    {
        return [
            'type' => $this->eventType,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];
    }

    /**
     * Handle a failed notification.
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error("Project Notification failed: {$this->eventType}", [
            'exception' => $exception->getMessage(),
            'notification' => static::class,
        ]);
    }
}
