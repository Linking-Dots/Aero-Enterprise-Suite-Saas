<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Enterprise;

use Aero\Platform\Models\Enterprise\KbArticle;
use Aero\Platform\Models\Enterprise\SupportTicket;
use Aero\Platform\Models\Enterprise\SupportTicketMessage;
use Aero\Platform\Services\Enterprise\HelpCenterService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P-11 — HelpCenterService
 *
 * Tests cover: KB article publish sets published_at,
 * and ticket reply creates SupportTicketMessage with correct ticket_id and body.
 */
class HelpCenterTest extends TestCase
{
    use DatabaseMigrations;

    protected HelpCenterService $service;

    public function runDatabaseMigrations(): void
    {
        $this->shareSqliteAcrossConnections();
        $this->artisan('migrate:fresh');
    }

    private function shareSqliteAcrossConnections(): void
    {
        $sqliteConfig = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.mysql' => $sqliteConfig, 'database.connections.central' => $sqliteConfig, 'tenancy.database.central_connection' => 'sqlite']);
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->service = app(HelpCenterService::class);
    }

    private function createArticle(string $title = 'Getting Started'): KbArticle
    {
        return KbArticle::create([
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(4),
            'body_md' => '# ' . $title,
            'is_published' => false,
        ]);
    }

    private function createTicket(string $subject = 'Need Help'): SupportTicket
    {
        return SupportTicket::create([
            'tenant_id' => (string) Str::uuid(),
            'user_email' => 'user@tenant.test',
            'subject' => $subject,
            'status' => 'open',
            'priority' => 'normal',
        ]);
    }

    // =========================================================================
    // KB ARTICLE — PUBLISH
    // =========================================================================

    public function test_article_publish_sets_published_at(): void
    {
        $article = $this->createArticle('How to configure SSO');

        $this->assertNull($article->published_at);
        $this->assertFalse($article->is_published);

        $published = $this->service->publishArticle($article);

        $this->assertTrue($published->is_published);
        $this->assertNotNull($published->published_at);

        $this->assertDatabaseHas('kb_articles', [
            'id' => $article->id,
            'is_published' => true,
        ]);

        $persisted = KbArticle::find($article->id);
        $this->assertNotNull($persisted->published_at);
    }

    public function test_article_publish_writes_audit_log(): void
    {
        $article = $this->createArticle('Billing FAQ');

        $this->service->publishArticle($article);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.help_center.article_published',
        ]);
    }

    public function test_article_create_persists_with_slug(): void
    {
        $article = $this->service->createArticle([
            'title' => 'API Reference Guide',
            'body_md' => '# API Reference',
            'category' => 'developer',
        ]);

        $this->assertNotEmpty($article->slug);
        $this->assertDatabaseHas('kb_articles', [
            'id' => $article->id,
            'title' => 'API Reference Guide',
        ]);
    }

    // =========================================================================
    // SUPPORT TICKETS — REPLY
    // =========================================================================

    public function test_ticket_reply_creates_message(): void
    {
        $ticket = $this->createTicket('Cannot export payroll data');
        $authorId = (string) Str::uuid();
        $body = 'Thank you for reaching out. We are investigating this issue.';

        $message = $this->service->replyTicket($ticket, [
            'author_type' => 'staff',
            'author_id' => $authorId,
            'body' => $body,
        ]);

        $this->assertInstanceOf(SupportTicketMessage::class, $message);
        $this->assertSame((string) $ticket->id, (string) $message->ticket_id);
        $this->assertSame($body, $message->body);

        $this->assertDatabaseHas('support_ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => 'staff',
            'author_id' => $authorId,
            'body' => $body,
        ]);
    }

    public function test_ticket_reply_moves_open_ticket_to_pending(): void
    {
        $ticket = $this->createTicket('Login issue');

        $this->assertSame('open', $ticket->status);

        $this->service->replyTicket($ticket, [
            'author_type' => 'staff',
            'author_id' => (string) Str::uuid(),
            'body' => 'We are looking into this.',
        ]);

        $ticket->refresh();
        $this->assertSame('pending', $ticket->status);
    }

    public function test_ticket_reply_writes_audit_log(): void
    {
        $ticket = $this->createTicket('Security concern');

        $this->service->replyTicket($ticket, [
            'author_type' => 'staff',
            'author_id' => (string) Str::uuid(),
            'body' => 'Our security team has been notified.',
        ]);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.help_center.ticket_replied',
        ]);
    }

    public function test_ticket_reply_preserves_existing_status_when_not_open(): void
    {
        $ticket = SupportTicket::create([
            'tenant_id' => (string) Str::uuid(),
            'user_email' => 'user@test.test',
            'subject' => 'Ongoing Issue',
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        $this->service->replyTicket($ticket, [
            'author_type' => 'staff',
            'author_id' => (string) Str::uuid(),
            'body' => 'Following up on this.',
        ]);

        $ticket->refresh();
        $this->assertSame('pending', $ticket->status);
    }

    public function test_multiple_replies_attach_to_correct_ticket(): void
    {
        $ticketA = $this->createTicket('Issue A');
        $ticketB = $this->createTicket('Issue B');

        $authorId = (string) Str::uuid();

        $this->service->replyTicket($ticketA, [
            'author_type' => 'staff',
            'author_id' => $authorId,
            'body' => 'Reply to A',
        ]);

        $this->service->replyTicket($ticketA, [
            'author_type' => 'staff',
            'author_id' => $authorId,
            'body' => 'Second reply to A',
        ]);

        $this->service->replyTicket($ticketB, [
            'author_type' => 'staff',
            'author_id' => $authorId,
            'body' => 'Reply to B',
        ]);

        $this->assertSame(2, SupportTicketMessage::where('ticket_id', $ticketA->id)->count());
        $this->assertSame(1, SupportTicketMessage::where('ticket_id', $ticketB->id)->count());
    }
}
