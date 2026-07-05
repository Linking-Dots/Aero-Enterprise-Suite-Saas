<?php

namespace Aero\HRM\Tests\Unit\Services;

use Aero\HRM\Contracts\NotifiableUserInterface;
use Aero\HRM\Services\HrmNotificationChannelResolver;
use PHPUnit\Framework\TestCase;

class HrmNotificationChannelResolverTest extends TestCase
{
    protected HrmNotificationChannelResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new HrmNotificationChannelResolver;
    }

    /**
     * Test resolver returns default channels when notifiable is null.
     */
    public function test_returns_default_channels_for_null_notifiable(): void
    {
        $channels = $this->resolver->resolve(null, 'leave_approved');

        $this->assertIsArray($channels);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    /**
     * Test resolver returns default channels for unknown event type.
     */
    public function test_returns_default_channels_for_unknown_event(): void
    {
        $channels = $this->resolver->resolve(null, 'unknown_event_type');

        $this->assertIsArray($channels);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    /**
     * Test resolver respects user preferences via interface.
     */
    public function test_respects_user_preferences_via_interface(): void
    {
        $notifiable = new class implements NotifiableUserInterface
        {
            public function getKey(): int|string
            {
                return 1;
            }

            public function getName(): string
            {
                return 'Test User';
            }

            public function getEmail(): string
            {
                return 'test@example.com';
            }

            public function routeNotificationForMail(): string
            {
                return 'test@example.com';
            }

            public function getPhoneNumber(): ?string
            {
                return null;
            }

            public function getFcmTokens(): array
            {
                return [];
            }

            public function wantsNotificationVia(string $eventType, string $channel): bool
            {
                // Only wants mail notifications for leave_approved
                if ($eventType === 'leave_approved' && $channel === 'mail') {
                    return true;
                }

                return false;
            }
        };

        $channels = $this->resolver->resolve($notifiable, 'leave_approved');

        $this->assertContains('mail', $channels);
        // Database should not be included because wantsNotificationVia returns false
        $this->assertNotContains('database', $channels);
    }

    /**
     * Test resolver includes fcm channel when enabled.
     */
    public function test_includes_fcm_channel_when_enabled(): void
    {
        $notifiable = new class implements NotifiableUserInterface
        {
            public function getKey(): int|string
            {
                return 1;
            }

            public function getName(): string
            {
                return 'Test User';
            }

            public function getEmail(): string
            {
                return 'test@example.com';
            }

            public function routeNotificationForMail(): string
            {
                return 'test@example.com';
            }

            public function getPhoneNumber(): ?string
            {
                return '+1234567890';
            }

            public function getFcmTokens(): array
            {
                return ['token123'];
            }

            public function wantsNotificationVia(string $eventType, string $channel): bool
            {
                // Wants all channels
                return true;
            }
        };

        $channels = $this->resolver->resolve($notifiable, 'leave_approved');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('fcm', $channels);
    }

    /**
     * Test resolver respects preferences from notification_preferences attribute.
     */
    public function test_respects_preferences_from_attribute(): void
    {
        $notifiable = new class
        {
            public array $notification_preferences = [
                'leave_approved' => [
                    'mail' => false,
                    'database' => true,
                    'fcm' => false,
                    'sms' => false,
                ],
            ];

            public function __get($name)
            {
                if ($name === 'notification_preferences') {
                    return $this->notification_preferences;
                }

                return null;
            }
        };

        $channels = $this->resolver->resolve($notifiable, 'leave_approved');

        $this->assertNotContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    /**
     * Test default channels for various event types.
     */
    public function test_default_channels_for_event_types(): void
    {
        // Leave events should have mail and database
        $leaveChannels = $this->resolver->getDefaultChannels('leave_approved');
        $this->assertContains('mail', $leaveChannels);
        $this->assertContains('database', $leaveChannels);

        // Birthday reminders should have mail, database, and fcm
        $birthdayChannels = $this->resolver->getDefaultChannels('birthday_reminder');
        $this->assertContains('mail', $birthdayChannels);
        $this->assertContains('database', $birthdayChannels);
        $this->assertContains('fcm', $birthdayChannels);

        // Payslip ready should have mail, database, and sms
        $payslipChannels = $this->resolver->getDefaultChannels('payslip_ready');
        $this->assertContains('mail', $payslipChannels);
        $this->assertContains('sms', $payslipChannels);
    }

    /**
     * Test fallback to default channels for unmapped events.
     */
    public function test_fallback_to_default_for_unmapped_events(): void
    {
        $channels = $this->resolver->getDefaultChannels('some_new_event_type');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }
}
