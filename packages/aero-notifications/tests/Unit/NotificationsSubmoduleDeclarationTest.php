<?php

declare(strict_types=1);

namespace Aero\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Plan 08 (aero-notifications) Task 1 — submodule declaration regression pin.
 *
 * Phase 1 audit found aero-notifications had ZERO submodule declarations
 * despite implementing a substantial surface (Email Engine, SMS Engine,
 * Push Engine, In-App Inbox, User Preferences, Global Settings).
 *
 * Without declarations, modules:sync wrote NOTHING for notifications,
 * HRMAC had no permissions to enforce, and the sidebar registry showed
 * nothing — the admin email/SMS/push pages were unreachable through
 * normal navigation.
 *
 * Also resolves the aero-core Task 12 ownership question — email_engine
 * was previously declared in aero-core/config/module.php where no
 * controllers exist for it; the canonical home is here.
 */
class NotificationsSubmoduleDeclarationTest extends TestCase
{
    private function config(): array
    {
        return require dirname(__DIR__, 2).'/config/module.php';
    }

    public function test_submodules_array_is_declared(): void
    {
        $config = $this->config();

        $this->assertArrayHasKey('submodules', $config,
            'aero-notifications/config/module.php MUST declare submodules — without ".
            "them modules:sync silently writes nothing.');

        $this->assertNotEmpty($config['submodules']);
    }

    public function test_email_engine_submodule_declared(): void
    {
        $codes = array_column($this->config()['submodules'], 'code');

        $this->assertContains('email_engine', $codes,
            'email_engine submodule MUST be declared here (moved from aero-core ".
            "Task 12). It is the canonical home for the email infrastructure ".
            "(EmailTemplateController, EmailLogController, SuppressionController, ".
            "DeliverabilityController, BounceController).');
    }

    public function test_sms_engine_submodule_declared(): void
    {
        $codes = array_column($this->config()['submodules'], 'code');
        $this->assertContains('sms_engine', $codes,
            'sms_engine submodule MUST be declared (SmsService + SmsGatewayService ".
            "+ SmsChannelAdapter all live in this package).');
    }

    public function test_push_engine_submodule_declared(): void
    {
        $codes = array_column($this->config()['submodules'], 'code');
        $this->assertContains('push_engine', $codes,
            'push_engine submodule MUST be declared (FcmNotificationService + ".
            "PushChannelAdapter live in this package).');
    }

    public function test_in_app_inbox_declared(): void
    {
        $codes = array_column($this->config()['submodules'], 'code');
        $this->assertContains('in_app', $codes,
            'in_app submodule MUST be declared for the user-facing notification inbox.');
    }

    public function test_preferences_declared(): void
    {
        $codes = array_column($this->config()['submodules'], 'code');
        $this->assertContains('preferences', $codes,
            'preferences submodule MUST be declared (UserNotificationPreference + ".
            "NotificationPreferenceController).');
    }

    public function test_settings_declared(): void
    {
        $codes = array_column($this->config()['submodules'], 'code');
        $this->assertContains('settings', $codes,
            'settings submodule MUST be declared (NotificationSettingController + ".
            "tenant-level NotificationSetting model).');
    }

    public function test_email_engine_has_required_components(): void
    {
        $emailEngine = collect($this->config()['submodules'])->firstWhere('code', 'email_engine');
        $componentCodes = array_column($emailEngine['components'], 'code');

        foreach (['templates', 'logs', 'suppression_list', 'deliverability', 'bounces'] as $required) {
            $this->assertContains($required, $componentCodes,
                "email_engine must include '{$required}' component — matches ".
                "the controllers shipped in src/Http/Controllers/Admin/ and ".
                "the previous aero-core declaration this fix is moving here.");
        }
    }
}
