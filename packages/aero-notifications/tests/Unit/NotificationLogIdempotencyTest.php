<?php

declare(strict_types=1);

namespace Aero\Notifications\Tests\Unit;

use Aero\Notifications\Models\NotificationLog;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 08 (aero-notifications) Task 2 — notification idempotency.
 *
 * Phase 1 audit found Horizon retries of failed SendEmailJob /
 * SendSmsJob could send DUPLICATE notifications: the first attempt
 * succeeded on the SMTP side but timed out on our end, Horizon
 * retried, second attempt also succeeded → tenant got two emails.
 *
 * The fix adds an idempotency_key column to notification_logs and
 * a unique (channel, idempotency_key) constraint. NotificationLog::
 * makeIdempotencyKey() computes a deterministic hash and
 * alreadyDispatched() short-circuits the second dispatch.
 *
 * Full integration test (forcing two NotificationPipeline runs with
 * the same payload and asserting only one row lands) requires real DB
 * and lives in the host feature suite. This file pins the API contract.
 */
class NotificationLogIdempotencyTest extends TestCase
{
    public function test_idempotency_key_column_is_fillable(): void
    {
        $instance = (new ReflectionClass(NotificationLog::class))->newInstanceWithoutConstructor();

        $this->assertContains('idempotency_key', $instance->getFillable(),
            'NotificationLog::$fillable must include idempotency_key so the '.
            'pipeline can set it on dispatch (Plan 08 T2).');
    }

    public function test_make_idempotency_key_method_exists(): void
    {
        $r = new ReflectionClass(NotificationLog::class);

        $this->assertTrue($r->hasMethod('makeIdempotencyKey'),
            'NotificationLog::makeIdempotencyKey() must exist (Plan 08 T2).');

        $method = $r->getMethod('makeIdempotencyKey');
        $this->assertTrue($method->isStatic(),
            'makeIdempotencyKey() must be static — called from jobs without an instance.');
    }

    public function test_already_dispatched_method_exists(): void
    {
        $r = new ReflectionClass(NotificationLog::class);

        $this->assertTrue($r->hasMethod('alreadyDispatched'),
            'NotificationLog::alreadyDispatched() must exist (Plan 08 T2) — '.
            'short-circuits NotificationPipeline duplicates.');
    }

    public function test_idempotency_key_is_deterministic(): void
    {
        $key1 = NotificationLog::makeIdempotencyKey('mail', 'user@example.com', ['template' => 'welcome', 'name' => 'Alice']);
        $key2 = NotificationLog::makeIdempotencyKey('mail', 'user@example.com', ['template' => 'welcome', 'name' => 'Alice']);

        $this->assertSame($key1, $key2,
            'Same channel + recipient + payload MUST produce the same key — '.
            'otherwise the dedupe-on-retry won\'t work.');
    }

    public function test_idempotency_key_payload_order_independent(): void
    {
        $key1 = NotificationLog::makeIdempotencyKey('mail', 'a@b.com', ['x' => 1, 'y' => 2]);
        $key2 = NotificationLog::makeIdempotencyKey('mail', 'a@b.com', ['y' => 2, 'x' => 1]);

        $this->assertSame($key1, $key2,
            'Payload key order MUST NOT change the idempotency hash — otherwise '.
            'serialization variance between retries would defeat the dedupe.');
    }

    public function test_idempotency_key_channel_scoped(): void
    {
        $mail = NotificationLog::makeIdempotencyKey('mail', 'a@b.com', ['msg' => 'hi']);
        $sms = NotificationLog::makeIdempotencyKey('sms', 'a@b.com', ['msg' => 'hi']);

        $this->assertNotSame($mail, $sms,
            'Mail and SMS with identical payload MUST get different keys — '.
            'sending the same content via two channels is legitimate.');
    }

    public function test_idempotency_key_recipient_scoped(): void
    {
        $alice = NotificationLog::makeIdempotencyKey('mail', 'alice@example.com', ['msg' => 'hi']);
        $bob = NotificationLog::makeIdempotencyKey('mail', 'bob@example.com', ['msg' => 'hi']);

        $this->assertNotSame($alice, $bob,
            'Different recipients with same payload MUST get different keys — '.
            'broadcasting the same message to N users is legitimate.');
    }

    public function test_idempotency_key_is_sha256_length(): void
    {
        $key = NotificationLog::makeIdempotencyKey('mail', 'a@b.com', []);

        $this->assertSame(64, strlen($key),
            'Key MUST be sha256 hex (64 chars) so the column varchar(64) is exactly sized.');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $key,
            'Key must be lowercase hex characters only.');
    }

    public function test_migration_exists(): void
    {
        $migrations = glob(dirname(__DIR__, 2).'/database/migrations/*_add_idempotency_to_notification_logs.php');

        $this->assertNotEmpty($migrations,
            'A migration adding idempotency_key + unique index must exist (Plan 08 T2).');
    }
}
